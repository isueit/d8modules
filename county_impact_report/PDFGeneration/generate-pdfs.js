/**
 * Generate PDFs for all 100 county impact reports from live sites
 * 
 * Instructions: 
 * 1. Create a new folder on your local machine
 *    mkdir ~/Desktop/county-pdfs
 * 2. Download node.js on local: https://nodejs.org/en/download
 * 3. Install Puppeteer in the folder you created on step 1: npm install puppeteer
 * 4. Place the generate-pdfs.js file into the same folder.
 * 5. Update the year in line 76
 * 6. When ready to generate report, run: node generate-pdfs.js from inside the folder.
 * 7. PDFs will be saved in a "pdfs" folder inside the folder you created on step 1.
 * 
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// List all 100 counties
const counties = [
  'adair', 'adams', 'allamakee', 'appanoose', 'audubon',
  'benton', 'blackhawk', 'boone', 'bremer', 'buchanan',
  'buenavista', 'butler', 'calhoun', 'carroll', 'cass',
  'cedar', 'cerrogordo', 'cherokee', 'chickasaw', 'clarke',
  'clay', 'clayton', 'clinton', 'crawford', 'dallas',
  'davis', 'decatur', 'delaware', 'desmoines', 'dickinson',
  'dubuque', 'emmet', 'fayette', 'floyd', 'franklin',
  'fremont', 'greene', 'grundy', 'guthrie', 'hamilton',
  'hancock', 'hardin', 'harrison', 'henry', 'howard',
  'humboldt', 'ida', 'iowa', 'jackson', 'jasper',
  'jefferson', 'johnson', 'jones', 'keokuk', 'kossuth',
  'lee', 'linn', 'louisa', 'lucas', 'lyon',
  'madison', 'mahaska', 'marion', 'marshall', 'mills',
  'mitchell', 'monona', 'monroe', 'montgomery', 'muscatine',
  'obrien', 'osceola', 'page', 'paloalto', 'plymouth',
  'pocahontas', 'polk', 'eastpottawattamie', 'westpottawattamie', 'poweshiek', 'ringgold',
  'sac', 'scott', 'shelby', 'sioux', 'story',
  'tama', 'taylor', 'union', 'van-buren', 'wapello',
  'warren', 'washington', 'wayne', 'webster', 'winnebago',
  'winneshiek', 'woodbury', 'worth', 'wright'
];

// Configuration
const config = {
  // Base URL pattern - UPDATE THIS to match your live sites
  baseUrl: 'https://www.extension.iastate.edu',
  // URL pattern for print pages - UPDATE THIS if different
  printPath: (county) => `/${county}/impact_report/print`,
  
  // Output directory
  outputDir: './pdfs',
  
  // Delay between requests (milliseconds) - be nice to the server
  delayBetweenRequests: 3000,
  
  // Debug mode - saves screenshots and HTML for troubleshooting
  debug: false,
  
  // PDF options
  pdfOptions: {
    format: 'Letter',
    printBackground: true,
    margin: {
      top: '0.5in',
      right: '0.5in',
      bottom: '0.5in',
      left: '0.5in'
    }
  }
};

async function generatePDF(county, browser) {
  const page = await browser.newPage();
  
  try {
    // First, navigate to /impact_report to get the actual node URL
    const vanityUrl = config.baseUrl + `/${county}/impact_report`;
    const outputPath = path.join(config.outputDir, `${county}-county-impact-report-2025.pdf`);
    
    console.log(`📄 Processing: ${county}`);
    console.log(`   Vanity URL: ${vanityUrl}`);
    
    // Navigate to vanity URL and let it redirect
    const response = await page.goto(vanityUrl, {
      waitUntil: 'networkidle2',
      timeout: 60000
    });
    
    // Check response status
    const status = response.status();
    console.log(`   Status: ${status}`);
    
    if (status === 404) {
      throw new Error('Page not found (404) - county may not have an impact report published');
    }
    
    if (status >= 400) {
      throw new Error(`HTTP error: ${status}`);
    }
    
    // Get the final URL after redirect
    const actualUrl = page.url();
    console.log(`   Actual URL: ${actualUrl}`);
    
    // Now navigate to the print version
    const printUrl = actualUrl + '/print';
    console.log(`   Print URL: ${printUrl}`);
    
    await page.goto(printUrl, {
      waitUntil: 'networkidle2',
      timeout: 60000
    });
    
    // Try multiple selectors in case the class name is different
    const selectors = [
      '.print-wrapper',
      '.print-page',
      'body',
      'main',
      '#content'
    ];
    
    let selectorFound = null;
    for (const selector of selectors) {
      try {
        await page.waitForSelector(selector, { timeout: 5000 });
        selectorFound = selector;
        console.log(`   Found selector: ${selector}`);
        break;
      } catch (e) {
        // Try next selector
      }
    }
    
    if (!selectorFound) {
      console.log('   ⚠️  Warning: No expected selectors found, generating PDF anyway...');
    }
    
    // Optional: Hide print buttons before generating PDF
    await page.evaluate(() => {
      const buttons = document.querySelector('.print-button-container');
      if (buttons) buttons.style.display = 'none';
      
      // Also hide any other common button containers
      const noPrint = document.querySelectorAll('.no-print');
      noPrint.forEach(el => el.style.display = 'none');
    });

    await page.pdf({
      path: outputPath,
      ...config.pdfOptions
    });

    console.log(`   ✓ Saved: ${outputPath}\n`);
    await page.close();
    return { success: true, county, url: actualUrl };
    
  } catch (error) {
    console.error(`   ✗ Error: ${error.message}\n`);
    await page.close();
    return { success: false, county, error: error.message };
  }
}

async function generateAllPDFs() {
  // Create output directory
  if (!fs.existsSync(config.outputDir)) {
    fs.mkdirSync(config.outputDir);
  }

  console.log('🚀 Starting PDF generation for all counties...');
  console.log(`📁 Output directory: ${path.resolve(config.outputDir)}\n`);

  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const results = [];
  const startTime = Date.now();
  
  for (let i = 0; i < counties.length; i++) {
    const county = counties[i];
    console.log(`[${i + 1}/${counties.length}]`);
    
    const result = await generatePDF(county, browser);
    results.push(result);
    
    // Delay between requests to be respectful to the server
    if (i < counties.length - 1) {
      await new Promise(resolve => setTimeout(resolve, config.delayBetweenRequests));
    }
  }

  await browser.close();

  // Summary
  const duration = ((Date.now() - startTime) / 1000 / 60).toFixed(2);
  const successful = results.filter(r => r.success);
  const failed = results.filter(r => !r.success);

  console.log('\n' + '='.repeat(50));
  console.log('📊 SUMMARY');
  console.log('='.repeat(50));
  console.log(`Total counties: ${results.length}`);
  console.log(`✓ Successful: ${successful.length}`);
  console.log(`✗ Failed: ${failed.length}`);
  console.log(`⏱️  Time: ${duration} minutes`);
  console.log(`📁 PDFs saved to: ${path.resolve(config.outputDir)}`);

  if (failed.length > 0) {
    console.log('\n❌ Failed counties:');
    failed.forEach(f => console.log(`   - ${f.county}: ${f.error}`));
  }
  
  console.log('\n✅ Done!');
}

// Run
generateAllPDFs().catch(console.error);