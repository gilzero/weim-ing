/**
 * @file
 * Checks if the quicklink library loaded.
 *
 * ```
 *    this.demoTest = function (client) {
 *      browser.assert.quicklinkExists();
 *    };
 * ```
 *
 * @method quicklinkExists
 * @api assertions
 */
QuicklinkExists = function () {
  this.message = `Testing the quicklink library has loaded`;
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
    return this.api.execute(function () {
      return typeof quicklink === "object";
    }, [], function (result) {
      callback.call(self, result)
    });
  }
};
module.exports.assertion = QuicklinkExists;
