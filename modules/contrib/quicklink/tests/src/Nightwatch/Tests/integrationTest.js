const originDomains = ['drupal.org', 'lullabot.com'];
const prefetchPaths = ['/foo', '/bar'];
const ignorePattern = ['ignore-me', 'test-ignore'];
const ignoreSelectors = [
  '.ignore-this-selector a',
  '#myId a',
  '[data-ignore-me*="xxx"] a',
  '#ignoreID'
]

module.exports = {
  '@tags': ['quicklink'],
  before: function (browser) {
    browser.drupalInstall({
      setupFile: 'modules/contrib/quicklink/tests/src/Nightwatch/TestSiteInstallTestScript.php',
    });
  },
  after: function (browser) {
    browser
      .drupalUninstall();
  },
  'Verify default settings': (browser) => {
    browser
      .drupalRelativeURL('/user')
      .assert.quicklinkExists()

      // Verify that elementIgnored assertation works properly. This link should get prefetched.
      .assert.not.elementIgnored('<a href="/getme">test</a>')

      // Verify that logout link is not prefetched.
      .assert.elementIgnored('<a href="/user/logout">Logout</a>')

      // Verify links with a 'noprefetch' attribute are not prefetched.
      .assert.elementIgnored('<a noprefetch href="/getme">test</a>')

      // Verify links with a 'download' attribute are not prefetched.
      .assert.elementIgnored('<a download href="/getme">test</a>')

      // Verify that links within admin containers are not prefetched.
      .assert.elementIgnored('<div id="block-local-tasks-block"><a href="/test">test</a></div>')
      .assert.elementIgnored('<div class="block-local-tasks-block"><a href="/test">test</a></div>')
      .assert.elementIgnored('<div id="drupal-off-canvas"><a href="/test">test</a></div>')
      .assert.elementIgnored('<div id="toolbar-administration"><a href="/test">test</a></div>')

      // Verify that admin paths are not prefetched.
      .assert.elementIgnored('<a href="/admin/test">test</a>')
      .assert.elementIgnored('<a href="/node/60/edit">test</a>')
      .assert.elementIgnored('<a href="/node/60/edit?destination=home">test</a>')

      // Verify that AJAX links are not prefetched.
      .assert.elementIgnored('<a href="/test/ajax">test</a>')
      .assert.elementIgnored('<a href="/test" class="use-ajax">test</a>')

      // Verify that hashes(#) are not prefetched.
      .assert.elementIgnored('<a href="/test#test">test</a>')

      // Verify that paths with file extensions are not prefetched.
      .assert.elementIgnored('<a href="/test.pdf">test</a>')
      .assert.elementIgnored('<a href="/test.pdf?xyz">test</a>')
      .assert.elementIgnored('<a href="/test.docx?xyz">test</a>')

      // Verify that the following custom selectors are currently prefetched.'
      .assert.not.elementIgnored('<span class="ignore-this-selector"><a href="/drupal">drupal</a></span>')
      .assert.not.elementIgnored('<span id="myId"><a href="/drupal">drupal</a></span>')
      .assert.not.elementIgnored('<span data-ignore-me="wwwxxxyyy"><a href="/drupal">drupal</a></span>')
      .assert.not.elementIgnored('<a id="ignoreID" href="/drupal">drupal</a>')

      // Verify "Override Parent Selector" is default.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.el === document;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Override Parent Selector" is default.');
        },
      )

      // Verify "Override allowed domains" is empty.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.origins === false;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Override allowed domains" is empty.');
        },
      )

      // Verify "Prefetch these paths only" is empty.
      .execute(
        function () {
          return typeof(drupalSettings.quicklink.quicklinkConfig.urls) === 'undefined';
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Prefetch these paths only" is empty.');
        },
      )

      // Verify "Debug mode" is disabled.
      .execute(
        function () {
          return drupalSettings.quicklink.debug === false;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Debug mode" is disabled.');
        },
      )

      // Verify debug_log does not exist.
      .execute(
        function () {
          return typeof (drupalSettings.quicklink.debug_log) === 'object';
        },
        [],
        (result) => {
          browser.assert.not.ok(result.value, 'Verify debug_log does not exist.');
        },
      )

      // Verify polyfill is not loaded.
      .assert.not.elementPresent('script[src*="polyfill-fastly.io"][src*="IntersectionObserver"]');
  },
  'Change and then verify updated settings': (browser) => {
    browser
      .drupalLoginAsAdmin(() => {
        browser
          .waitForElementVisible('body')
          // Verify that Quicklink is not loaded when authenticated.
          .assert.not.quicklinkExists()

          // Create "Enabled" node. This will be node/1.
          .drupalRelativeURL('/node/add/enabled')
          .waitForElementVisible('[data-drupal-selector="edit-title-0-value"]')
          .setValue('[data-drupal-selector="edit-title-0-value"]', 'Enabled')
          .setValue('[data-drupal-selector="edit-body-0-value"]', 'Enabled')
          .submitForm('[data-drupal-selector="edit-submit"]')

          // Create "Disabled" node. This will be node/2.
          .drupalRelativeURL('/node/add/disabled')
          .waitForElementVisible('[data-drupal-selector="edit-title-0-value"]')
          .setValue('[data-drupal-selector="edit-title-0-value"]', 'Disabled')
          .setValue('[data-drupal-selector="edit-body-0-value"]', 'Disabled')
          .submitForm('[data-drupal-selector="edit-submit"]')

          // Navigate to Quicklink settings.
          .drupalRelativeURL('/admin/config/development/performance/quicklink')

          // Uncheck "Do not prefetch admin paths".
          .click('[data-drupal-selector="edit-ignore-admin-paths"]')

          // Uncheck "Do not prefetch AJAX links".
          .click('[data-drupal-selector="edit-ignore-ajax-links"]')

          // Uncheck "Ignore paths with hashes (#) in them".
          .click('[data-drupal-selector="edit-ignore-hashes"]')

          // Uncheck "Ignore paths with file extensions".
          .click('[data-drupal-selector="edit-ignore-file-ext"]')

          // Add some values into "URL patterns to ignore (optional)".
          .setValue('[data-drupal-selector="edit-url-patterns-to-ignore"]', `${ignorePattern[0]}\r\n${ignorePattern[1]}`)

          // Add values into "Ignore these selectors (optional)".
          .setValue('[data-drupal-selector="edit-ignore-selectors"]', ignoreSelectors.join('\r\n'))

          // Navigate to "Optional Overrides" tab.
          .click('link text', 'Optional Overrides')
          .waitForElementVisible('[data-drupal-selector="edit-selector"]')

          // Set non-default value within "Override parent selector (optional)".
          .setValue('[data-drupal-selector="edit-selector"]', 'body')

          // Set non-default value in "Override allowed domains (optional)".
          .setValue('[data-drupal-selector="edit-allowed-domains"]', originDomains.join('\r\n'))

          // Navigate to "When to Load Library" tab.
          .click('link text', 'When to Load Library')
          .waitForElementVisible('[data-drupal-selector="edit-no-load-when-authenticated"]')

          // Uncheck "Prefetch for anonymous users only".
          .click('[data-drupal-selector="edit-no-load-when-authenticated"]')

          // Uncheck "Do not prefetch during sessions".
          .click('[data-drupal-selector="edit-no-load-when-session"]')

          // Check the checkbox for the "disabled" content type under "Do not load library on these content types."
          .click('[data-drupal-selector="edit-no-load-content-types-disabled"]')

          // Navigate to "Throttle Options" tab.
          .click('link text', 'Throttle Options')
          .waitForElementVisible('[data-drupal-selector="edit-total-request-limit"]')

          // Change the "Set request limit" value.
          .clearValue('[data-drupal-selector="edit-total-request-limit"]')
          .setValue('[data-drupal-selector="edit-total-request-limit"]', 10)

          // Change the "Set concurrency throttle" value.
          .clearValue('[data-drupal-selector="edit-concurrency-throttle-limit"]')
          .setValue('[data-drupal-selector="edit-concurrency-throttle-limit"]', 10)

          // Change the "Set idle timeout value" value.
          .clearValue('[data-drupal-selector="edit-idle-wait-timeout"]')
          .setValue('[data-drupal-selector="edit-idle-wait-timeout"]', 4000)

          // Change the "Viewport Delay" value.
          .clearValue('[data-drupal-selector="edit-viewport-delay"]')
          .setValue('[data-drupal-selector="edit-viewport-delay"]', 100)

          // Navigate to "Prefetch Paths Only" tab.
          .click('link text', 'Prefetch Paths Only')

          // Set non-default value in "Prefetch these paths only (overrides everything else)".
          .setValue('[data-drupal-selector="edit-prefetch-only-paths"]', prefetchPaths.join('\r\n'))

          // Navigate to "Extended Browser Support" tab.
          .click('link text', 'Extended Browser Support')
          .waitForElementVisible('[data-drupal-selector="edit-load-polyfill"]')

          // Check "Load Intersection Observer polyfill".
          .click('[data-drupal-selector="edit-load-polyfill"]')

          // Navigate to "Debug" tab.
          .click('link text', 'Debug')
          .waitForElementVisible('[data-drupal-selector="edit-enable-debug-mode"]')

          // Check the "Enable debug mode" checkbox.
          .click('[data-drupal-selector="edit-enable-debug-mode"]')

          // Submit the form.
          .submitForm('[data-drupal-selector="quicklink-config"]')
          .waitForElementVisible('body')

          // Verify that Quicklink now exists while authenticated.
          .assert.quicklinkExists();
      })
      .drupalRelativeURL('/user')
      .waitForElementVisible('body')

      // Verify polyfill is loaded.
      .assert.elementPresent('script[src*="polyfill-fastly.io"][src*="IntersectionObserver"]')

      // Verify that links within admin containers are now prefetched.
      .assert.not.elementIgnored('<div id="block-local-tasks-block"><a href="/test">test</a></div>')
      .assert.not.elementIgnored('<div class="block-local-tasks-block"><a href="/test">test</a></div>')
      .assert.not.elementIgnored('<div id="drupal-off-canvas"><a href="/test">test</a></div>')
      .assert.not.elementIgnored('<div id="toolbar-administration"><a href="/test">test</a></div>')

      // Verify that AJAX links are now prefetched.
      .assert.not.elementIgnored('<a href="/test/ajax">test</a>')
      .assert.not.elementIgnored('<a href="/test" class="use-ajax">test</a>')

      // Verify that hashes(#) are now prefetched.
      .assert.not.elementIgnored('<a href="/test#test">test</a>')

      // Verify that paths with file extensions are now prefetched.
      .assert.not.elementIgnored('<a href="/test.pdf">test</a>')
      .assert.not.elementIgnored('<a href="/test.pdf?xyz">test</a>')
      .assert.not.elementIgnored('<a href="/test.docx?xyz">test</a>')

      // Verify that the following custom URL patterns are now not prefetched.
      .assert.elementIgnored(`<a href="/test/${ignorePattern[0]}/test">test</a>`)
      .assert.elementIgnored(`<a href="/${ignorePattern[1]}/test">test</a>`)
      .assert.elementIgnored(`<a href="test/${ignorePattern[1]}">test</a>`)
      .assert.elementIgnored(`<a href="test/test?${ignorePattern[0]}">test</a>`)
      .assert.elementIgnored(`<a href="test/test#${ignorePattern[0]}">test</a>`)

       // Verify that the following custom selectors are now not prefetched.'
      .assert.elementIgnored('<span class="ignore-this-selector"><a href="/drupal">drupal</a></span>')
      .assert.elementIgnored('<span id="myId"><a href="/drupal">drupal</a></span>')
      .assert.elementIgnored('<span data-ignore-me="wwwxxxyyy"><a href="/drupal">drupal</a></span>')
      .assert.elementIgnored('<a id="ignoreID" href="/drupal">drupal</a>')

      // Verify "Override Parent Selector" is now set to the new value.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.el === document.body;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Override Parent Selector" is now set to the new value.');
        },
      )

      // Verify "Override allowed domains" now has new values.
      .execute(
        function (originDomains) {
          return originDomains.every(domain => drupalSettings.quicklink.quicklinkConfig.origins.includes(domain));
        },
        [originDomains],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Override allowed domains" now has new values.');
        },
      )

      // Verify "Prefetch these paths only" now has new values.
      .execute(
        function (prefetchPaths) {
          return prefetchPaths.every(path => drupalSettings.quicklink.prefetch_only_paths.includes(path));
        },
        [prefetchPaths],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Prefetch these paths only" now has new values.');
        },
      )

      // Verify "Debug mode" is enabled.
      .execute(
        function () {
          return drupalSettings.quicklink.debug === 1;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify "Debug mode" is enabled.');
        },
      )

      // Verify debug_log object exists.
      .execute(
        function () {
          return typeof(drupalSettings.quicklink.debug_log) === 'object';
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify debug_log object exists.');
        },
      )

      // Verify that custom patterns appear in url_patterns_to_ignore debug array.
      .execute(
        function (ignorePattern) {
          return ignorePattern.every(pattern => drupalSettings.quicklink.url_patterns_to_ignore.includes(pattern));
        },
        [ignorePattern],
        (result) => {
          browser.assert.ok(result.value, 'Verify that custom patterns appear in url_patterns_to_ignore debug array.');
        },
      )

      // Verify the "Set request limit" value.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.limit === 10;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify the "Set request limit" value.');
        },
      )

      // Verify the "Set concurrency throttle" value.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.throttle === 10;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify the "Set concurrency throttle" value.');
        },
      )

      // Verify the "Set idle timeout value" value.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.timeout === 4000;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify the "Set idle timeout value" value.');
        },
      )

      // Verify the "Viewport Delay" value.
      .execute(
        function () {
          return drupalSettings.quicklink.quicklinkConfig.delay === 100;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify the "Viewport Delay" value.');
        },
      )

      // Navigate to "Enabled" content type node, and verify Quicklink exists.
      .drupalRelativeURL('/node/1')
      .waitForElementVisible('body')
      .assert.quicklinkExists()

      // Navigate to "Disabled" content type node, and verify Quicklink does not exist.
      .drupalRelativeURL('/node/2')
      .waitForElementVisible('body')
      .assert.not.quicklinkExists();

  },
  'Disable debug mode and ensure script is not loaded when not needed': (browser) => {
    browser
      .drupalLoginAsAdmin(() => {
        browser
          .waitForElementVisible('body')

          // Navigate to Quicklink settings.
          .drupalRelativeURL('/admin/config/development/performance/quicklink')
          .waitForElementVisible('body')

          // Navigate to "Debug" tab.
          .click('link text', 'Debug')
          .waitForElementVisible('[data-drupal-selector="edit-enable-debug-mode"]')

          // Uncheck the "Enable debug mode" checkbox.
          .click('[data-drupal-selector="edit-enable-debug-mode"]')

          // Submit the form.
          .submitForm('[data-drupal-selector="quicklink-config"]')
          .waitForElementVisible('body')
      })
      // Navigate to the "Disabled" content type node.
      .drupalRelativeURL('/node/2')
      .waitForElementVisible('body')

      .assert.not.quicklinkExists()

      // Verify init script is not loaded.
      .assert.not.elementPresent('script[src*="quicklink"]');
    }
};
