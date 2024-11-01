/**
 * @file Contains shims to avoid breaking older versions of CKEditor 5.
 */

import { ViewModel, Model } from 'ckeditor5/src/ui';

// Older versions of CKEditor 5 expect the export to be named `Model` rather
// than `ViewModel`. See https://github.com/ckeditor/ckeditor5/issues/15661
// This shim allows CKEditor 5 Icons to retain compatibility with Drupal 10.2
// and earlier.
export const UiViewModel = typeof ViewModel !== 'undefined' ? ViewModel : Model;
