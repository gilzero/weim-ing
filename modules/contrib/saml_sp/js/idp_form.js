/**
 * @file
 * Provide interactions in the IdP configuration form.
 */

(($, Drupal) => {
  Drupal.samlSp = Drupal.samlSp || {};
  Drupal.samlSp.certs = {};

  Drupal.samlSp.idpMetadataParse = () => {
    const xml = $.parseXML(
      $('textarea[name="idp_metadata"]')
        .val()
        .trim()
    );
    Drupal.samlSp.idpMetadataXML = $(xml);
    const entityID = Drupal.samlSp.idpMetadataXML
      .find("EntityDescriptor, md\\:EntityDescriptor")
      .attr("entityID");

    // We will need to explicitly call Drupal.samlSp.addCert() unless
    // the IdP name is changed below, which triggers it via Ajax.
    let addCert = true;

    if (typeof entityID === "string" && entityID !== "") {
      $("input#edit-idp-entity-id").val(entityID.trim());
      const parser = document.createElement("a");
      parser.href = entityID;
      let label = $("input#edit-idp-label")
        .val()
        .trim();
      if (label === "") {
        const org = Drupal.samlSp.idpMetadataXML.find(
          "OrganizationDisplayName"
        );
        label = org.text() ? $(org[0]).text() : parser.hostname;
        if (label.length > 30) {
          label = label.substr(0, 30);
        }
        $("input#edit-idp-label")
          .val(label)
          .change();
        addCert = false;
      }
    }

    Drupal.samlSp.idpMetadataXML
      .find("SingleSignOnService, md\\:SingleSignOnService")
      .each(function sso() {
        if (
          $(this).attr("Binding") ===
          "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
        ) {
          $("input#edit-idp-login-url").val(
            $(this)
              .attr("Location")
              .trim()
          );
        }
      });

    Drupal.samlSp.idpMetadataXML
      .find("SingleLogoutService, md\\:SingleLogoutService")
      .each(function slo() {
        if (
          $(this).attr("Binding") ===
          "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
        ) {
          $("input#edit-idp-logout-url").val(
            $(this)
              .attr("Location")
              .trim()
          );
        }
      });

    Drupal.samlSp.idpMetadataXML
      .find(
        "KeyDescriptor, md\\:KeyDescriptor, X509Certificate, ds\\:X509Certificate"
      )
      .each(function x509() {
        // We put the certs in an object to ensure that none are duplicated.
        // The certs need to have all whitespace removed.
        const cert = $(this)
          .text()
          .replace(/\s+/g, "");
        Drupal.samlSp.certs[cert] = true;
      });
    if (addCert) {
      Drupal.samlSp.addCert();
    }
  };

  /**
   * Add one cert to the form, trigger the "Add one more" action.
   */
  Drupal.samlSp.addCert = () => {
    if (Object.keys(Drupal.samlSp.certs).length === 0) {
      return;
    }
    const cert = Object.keys(Drupal.samlSp.certs)[0];
    const textarea = $("textarea[data-drupal-selector*=edit-idp-x509-cert-]");
    $(textarea[textarea.length - 1]).val(cert);
    delete Drupal.samlSp.certs[cert];
    $(
      "input:submit[data-drupal-selector=edit-idp-x509-cert-actions-add-cert]"
    ).mousedown();
  };

  /**
   * Ensures that Ajax submissions have completed before starting a new one.
   */
  $(document).ajaxComplete((event, xhr, settings) => {
    if (
      settings.url.search("machine_name/transliterate") !== -1 ||
      settings.url.search("saml_sp/idp/add") !== -1
    ) {
      Drupal.samlSp.addCert();
    }
  });

  /**
   * Watches the IdP metadata field to parse it into constituent fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches keyup and mouseup responses to the IdP metadata field.
   */
  Drupal.behaviors.samlSpIdpForm = {
    attach: context => {
      $('textarea[name="idp_metadata"]:not(.idp-form-processed)', context)
        .addClass("idp-form-processed")
        .keyup(() => {
          Drupal.samlSp.idpMetadataParse();
        })
        .mouseup(() => {
          Drupal.samlSp.idpMetadataParse();
        });
    }
  };
})(jQuery, Drupal);
