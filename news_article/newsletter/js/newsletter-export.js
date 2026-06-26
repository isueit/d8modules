// js/newsletter-export.js

(function (Drupal) {
  'use strict';

  Drupal.behaviors.newsletterExport = {
    attach: function (context, settings) {
      const copyButton = document.getElementById('copy-button');
      const textarea = document.getElementById('newsletter-html');

      if (copyButton && textarea) {
        copyButton.addEventListener('click', function() {
          // Select the text
          textarea.select();
          textarea.setSelectionRange(0, 99999); // For mobile devices

          // Copy to clipboard
          try {
            navigator.clipboard.writeText(textarea.value).then(function() {
              // Success feedback
              copyButton.textContent = 'Copied!';
              copyButton.classList.add('copied');
              
              // Reset button after 2 seconds
              setTimeout(function() {
                copyButton.textContent = 'Copy HTML to Clipboard';
                copyButton.classList.remove('copied');
              }, 2000);
            }).catch(function(err) {
              // Fallback for older browsers
              document.execCommand('copy');
              copyButton.textContent = 'Copied!';
              copyButton.classList.add('copied');
              
              setTimeout(function() {
                copyButton.textContent = 'Copy HTML to Clipboard';
                copyButton.classList.remove('copied');
              }, 2000);
            });
          } catch (err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy. Please manually select and copy the text.');
          }
        });
      }
    }
  };

})(Drupal);