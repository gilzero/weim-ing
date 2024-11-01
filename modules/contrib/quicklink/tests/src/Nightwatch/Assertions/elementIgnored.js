/**
 * @file
 * Checks passed in HTML will be ignored by Quicklink
 *
 * ```
 *    this.demoTest = function (client) {
 *      browser.assert.elementIgnored('<a href="/user/logout">test</a>');
 *    };
 * ```
 *
 * @method elementIgnored
 * @api assertions
 */
elementIgnored = function (html) {
  this.message = `Testing if the following link HTML is ignored (or not):
    ${html}
  `;
  this.expected = function () {
    return true;
  };
  this.pass = function (value) {
    return value === true;
  };
  this.value = (result) => {
    return result.value;
  };
  this.command = function (callback) {
    const self = this;
    return this.api.execute(function (html) {
      const ignores = drupalSettings.quicklink.quicklinkConfig.ignores;
      const testEl = document.createElement('div');
      testEl.innerHTML = html;
      const testLink = testEl.querySelector('a');
      let result = false;
      ignores.forEach(func => func(testLink.getAttribute('href'), testLink) ? result = true : false);
      return result;
    }, [html], function (result) {
      callback.call(self, result)
    });
  }
};
module.exports.assertion = elementIgnored;
