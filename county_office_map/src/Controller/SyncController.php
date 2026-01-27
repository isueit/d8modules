<?php

namespace Drupal\county_office_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Exception\RequestException;

/**
 * Controller for syncing county office data.
 */
class SyncController extends ControllerBase {

  /**
   * Sync a single county's data.
   */
  public function syncCounty($county_tid) {
    $term = Term::load($county_tid);
    
    if (!$term) {
      $this->messenger()->addError($this->t('County not found.'));
      return $this->redirect('county_office_map.sync_admin');
    }
    
    $result = $this->syncCountyData($term);
    
    if ($result['success']) {
      $this->messenger()->addStatus($this->t('Successfully synced @county County', [
        '@county' => $term->label(),
      ]));
      
      // Show what was found
      if (!empty($result['data'])) {
        $found = [];
        if (!empty($result['data']['address'])) $found[] = 'Address';
        if (!empty($result['data']['email'])) $found[] = 'Email';
        if (!empty($result['data']['phone'])) $found[] = 'Phone';
        if (!empty($result['data']['hours'])) $found[] = 'Hours';
        
        if (!empty($found)) {
          $this->messenger()->addStatus($this->t('Found: @fields', [
            '@fields' => implode(', ', $found),
          ]));
        }
      }
    } else {
      $this->messenger()->addError($this->t('Failed to sync @county County: @error', [
        '@county' => $term->label(),
        '@error' => $result['message'],
      ]));
    }
    
    return $this->redirect('county_office_map.sync_admin');
  }

  /**
   * Sync all counties.
   */
  public function syncAll() {
    $vocabulary_name = 'counties'; // TODO: Update to your vocab name
    
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary_name);
    
    $success_count = 0;
    $fail_count = 0;
    $errors = [];
    
    foreach ($terms as $term) {
      $term_obj = Term::load($term->tid);
      if (!$term_obj) {
        continue;
      }
      
      // Skip if manual override is checked
      if ($term_obj->hasField('field_manual_override') && $term_obj->get('field_manual_override')->value) {
        continue;
      }
      
      $result = $this->syncCountyData($term_obj);
      
      if ($result['success']) {
        $success_count++;
      } else {
        $fail_count++;
        $errors[] = $term->name . ': ' . $result['message'];
      }
      
      // Small delay to be nice to servers
      usleep(500000); // 0.5 second delay
    }
    
    $this->messenger()->addStatus($this->t('Sync complete: @success successful, @fail failed', [
      '@success' => $success_count,
      '@fail' => $fail_count,
    ]));
    
    if (!empty($errors)) {
      foreach (array_slice($errors, 0, 10) as $error) {
        $this->messenger()->addWarning($error);
      }
      if (count($errors) > 10) {
        $this->messenger()->addWarning($this->t('... and @more more errors', [
          '@more' => count($errors) - 10,
        ]));
      }
    }
    
    return $this->redirect('county_office_map.sync_admin');
  }

  /**
   * Sync data for a specific county term.
   */
  private function syncCountyData(Term $term) {
    // Get the county's website URL
    if (!$term->hasField('field_website') || $term->get('field_website')->isEmpty()) {
      return [
        'success' => FALSE,
        'message' => 'No website URL configured',
      ];
    }
    
    $website_url = $term->get('field_website')->uri;
    
    // Fetch and scrape the homepage
    try {
      $client = \Drupal::httpClient();
      $response = $client->get($website_url, [
        'timeout' => 15,
        'headers' => [
          'User-Agent' => 'ISU Extension Data Sync Bot',
        ],
      ]);
      
      $html = (string) $response->getBody();
      
      // Extract contact information
      $data = $this->extractContactInfo($html, $term->label());
      
      if (empty($data)) {
        return [
          'success' => FALSE,
          'message' => 'No contact information found on homepage',
        ];
      }
      
      return $this->updateCountyFields($term, $data);
      
    } catch (RequestException $e) {
      return [
        'success' => FALSE,
        'message' => 'Failed to fetch website: ' . $e->getMessage(),
      ];
    } catch (\Exception $e) {
      return [
        'success' => FALSE,
        'message' => 'Error: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Extract contact information from HTML.
   */
  private function extractContactInfo($html, $county_name) {
    $data = [];
    
    // Create a DOMDocument to parse HTML
    $dom = new \DOMDocument();
    
    // Suppress errors from malformed HTML
    libxml_use_internal_errors(TRUE);
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    $xpath = new \DOMXPath($dom);
    
    // Strategy: Look in both the sidebar block and the footer
    
    // 1. Extract Office Hours
    // Look for the contact_hours class
    $hours_nodes = $xpath->query("//div[@class='contact_hours']");
    if ($hours_nodes && $hours_nodes->length > 0) {
      $hours = trim($hours_nodes->item(0)->nodeValue);
      if (!empty($hours)) {
        $data['hours'] = $hours;
      }
    }
    
    // 2. Extract Email
    // Look for mailto links
    $email_nodes = $xpath->query("//a[starts-with(@href, 'mailto:')]");
    if ($email_nodes && $email_nodes->length > 0) {
      foreach ($email_nodes as $email_node) {
        $href = $email_node->getAttribute('href');
        $email = str_replace('mailto:', '', $href);
        $email = trim($email);
        
        // Make sure it looks like a real email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $data['email'] = $email;
          break; // Use the first valid email found
        }
      }
    }
    
    // 3. Extract Phone
    // Look for phone numbers - they're typically in a div near email
    // Pattern: (XXX) XXX-XXXX or similar
    preg_match_all('/\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', $html, $phone_matches);
    if (!empty($phone_matches[0])) {
      $data['phone'] = trim($phone_matches[0][0]);
    }
    
    // 4. Extract Address
    // Look for address patterns - typically has <br> tags and zip code
    // The address is usually in a div that contains the county name or is near the email
    
    // Try to find divs that contain both street address patterns and Iowa zip codes
    $all_divs = $xpath->query("//div");
    foreach ($all_divs as $div) {
      $text = $div->nodeValue;
      
      // Check if this div contains an Iowa zip code (5XXXX pattern)
      if (preg_match('/\b5\d{4}\b/', $text)) {
        // Check if it also contains street-like patterns
        if (preg_match('/\d+\s+[A-Z]/', $text)) {
          // Clean up the text
          $address = $this->cleanAddressText($div);
          if (!empty($address)) {
            $data['address'] = $address;
            break;
          }
        }
      }
    }
    
    // Alternative address extraction: Look specifically in the contact blocks
    if (empty($data['address'])) {
      // Look in the footer contact block
      $footer_address = $xpath->query("//div[@id='block-contact-us']//div[contains(., '5')]");
      if ($footer_address && $footer_address->length > 0) {
        $address = $this->cleanAddressText($footer_address->item(0));
        if (!empty($address)) {
          $data['address'] = $address;
        }
      }
    }
    
    // Still no address? Try the sidebar region
    if (empty($data['address'])) {
      $sidebar_address = $xpath->query("//div[@class='layout__region layout__region--second']//div[contains(., '5')]");
      if ($sidebar_address && $sidebar_address->length > 0) {
        foreach ($sidebar_address as $node) {
          $address = $this->cleanAddressText($node);
          if (!empty($address) && strlen($address) > 10) {
            $data['address'] = $address;
            break;
          }
        }
      }
    }
    
    return $data;
  }

  /**
   * Clean up address text from a DOM node.
   */
  private function cleanAddressText($node) {
    // Get the HTML content of the node
    $dom = $node->ownerDocument;
    $html = $dom->saveHTML($node);
    
    // Replace <br> tags with newlines
    $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
    
    // Strip remaining HTML tags
    $text = strip_tags($html);
    
    // Clean up whitespace
    $lines = explode("\n", $text);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, function($line) {
      return !empty($line);
    });
    
    // Join with newlines
    $address = implode("\n", $lines);
    
    // Remove any "Contact Information:" or similar headers
    $address = preg_replace('/^(Contact|Address).*?:/i', '', $address);
    $address = trim($address);
    
    return $address;
  }

  /**
   * Update county term fields with fetched data.
   */
  private function updateCountyFields(Term $term, array $data) {
    try {
      $updated = FALSE;
      
      // Update address
      if (isset($data['address']) && $term->hasField('field_contact_creator_address')) {
        $term->set('field_contact_creator_address', $data['address']);
        $updated = TRUE;
      }
      
      // Update email
      if (isset($data['email']) && $term->hasField('field_contact_creator_email')) {
        $term->set('field_contact_creator_email', $data['email']);
        $updated = TRUE;
      }
      
      // Update phone
      if (isset($data['phone']) && $term->hasField('field_contact_creator_phone')) {
        $term->set('field_contact_creator_phone', $data['phone']);
        $updated = TRUE;
      }
      
      // Update hours
      if (isset($data['hours']) && $term->hasField('field_contact_creator_hours')) {
        $term->set('field_contact_creator_hours', $data['hours']);
        $updated = TRUE;
      }
      
      // Set last synced date if field exists
      if ($term->hasField('field_last_synced')) {
        $term->set('field_last_synced', time());
      }
      
      if ($updated) {
        $term->save();
        
        return [
          'success' => TRUE,
          'message' => 'Data synced successfully',
          'data' => $data,
        ];
      } else {
        return [
          'success' => FALSE,
          'message' => 'No fields were updated',
        ];
      }
      
    } catch (\Exception $e) {
      return [
        'success' => FALSE,
        'message' => $e->getMessage(),
      ];
    }
  }

}