((Drupal, once) => {
  'use strict';

  Drupal.behaviors.THEMENAME_MdcFunctions = {
    attach: (context, settings) => {

      // Put your common functions and handlers here. For example:
      //
      // const myCommonFunction = (element, event) => {
      //   // Do something.
      // }

      // Put global page behaviors here.
      // It is Drupal's equivalent JQuery's $(document).ready(myInit());
      // Example:
      //
      // once('myGlobalBehaviors', 'html').forEach(() => {
      //   const singleElement = document.getElementById('element-id');
      //   singleElement.addEventListener('click', event => myCommonFunction(singleElement, event));
      //
      //   const multipleElements = document.querySelectorAll('.classname-selector');
      //   multipleElements.forEach(element => {
      //     element.addEventListener('click', event => myCommonFunction(element, event));
      //   });
      // });

      // Put your specific behaviors with Ajax loading support here. For example:
      //
      // once('mySpecificBehavior', '.classname-selector', context).forEach(element => {
      //   element.addEventListener('click', event => myCommonFunction(element, event));
      // });

    }
  };
}) (Drupal, once);
