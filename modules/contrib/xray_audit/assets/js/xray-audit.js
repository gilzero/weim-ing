/**
 * @file
 * xray_audit visual help.
 */

(function (Drupal, once) {

  const containerSelector = ".xray-audit__table";
  const fieldSelector = ".xray-audit__table .xray-audit__field";

  Drupal.behaviors.xrayAudit = {
    attach(context) {
      const elements = once("table-visual-help", fieldSelector, context);

      elements.forEach((item) => {
        item.addEventListener("mouseenter", enableHighlight, { passive: true });
        item.addEventListener("mouseleave", disableHighlight, { passive: true });
      }, elements);
    }
  }
  function enableHighlight() {
    let target = this.dataset.highlightTarget;
    let context = this.closest(".xray-audit__row");
    context.querySelectorAll(fieldSelector + ":not(." + target + ")").forEach((item) => {
      item.classList.add("xray-audit--transparent");
    });
  }
  function disableHighlight() {
    document.querySelectorAll(fieldSelector).forEach((item) => {
      item.classList.remove("xray-audit--transparent");
    });
  }
}(Drupal, once));
