/**
 * @file defines InsertIconCommand, which is executed when the icon toolbar button is pressed.
 */
// cSpell:ignore linkui

import { Command } from 'ckeditor5/src/core';
import type { DocumentSelection, Element, Model, ViewAttributeElement, ViewPosition } from 'ckeditor5/src/engine';
import { findOptimalInsertionRange, isWidget } from 'ckeditor5/src/widget';
import { isLinkElement } from '@ckeditor/ckeditor5-link/src/utils';

/**
 * Represents a command which is executed when the icon toolbar button is pressed.
 */
export default class InsertIconCommand extends Command {
  /**
   * @inheritdoc
   */
  public override execute({ iconFA, iconStyle }) {
    const { editing, model } = this.editor;

    model.change((writer) => {
      // Insert <icon></icon> at the current selection position
      // in a way that will result in creating a valid model structure.
      let linkHref: string | undefined;
      const linkElement = this._getSelectedLinkElement();
      if (linkElement)
        linkHref = linkElement.getAttribute('href');
      const iconElement = writer.createElement('icon', linkHref ? { linkHref, iconFA, iconStyle } : { iconFA, iconStyle });
      model.insertContent(iconElement);
      editing.view.focus();
      writer.setSelection(iconElement, 'on');
    });
  }

  /**
   * @inheritdoc
   */
  public override refresh() {
    const { model } = this.editor;
    const { document, schema } = model;

    // Determine if the cursor (selection) is in a position where adding a
    // icon is permitted. This is based on the schema of the model(s)
    // currently containing the cursor.
    const allowedIn = schema.checkChild(getParentElement(document.selection, model), 'icon');

    // If the cursor is not in a location where a icon can be added, return
    // null so the addition doesn't happen.
    this.isEnabled = allowedIn !== null;
  }

  /**
   * Gets a selected link element if there is one, otherwise returns `null`.
   * This enables icons to be correctly inserted inside links.
   *
   * {@link module:link/linkui~LinkUI#_getSelectedLinkElement Duplicates a private function in the `link` module.}
   */
  private _getSelectedLinkElement(): ViewAttributeElement | null {
    const view = this.editor.editing.view;
    const selection = view.document.selection;
    const selectedElement = selection.getSelectedElement();

    // The selection is collapsed or some widget is selected (especially inline widget).
    if (selection.isCollapsed || selectedElement && isWidget(selectedElement)) {
      return findLinkElementAncestor(selection.getFirstPosition()!);
    } else {
      // The range for fully selected link is usually anchored in adjacent text nodes.
      // Trim it to get closer to the actual link element.
      const range = selection.getFirstRange()!.getTrimmed();
      const startLink = findLinkElementAncestor(range.start);
      const endLink = findLinkElementAncestor(range.end);

      if (!startLink || startLink != endLink) {
        return null;
      }

      // Check if the link element is fully selected.
      if (view.createRangeIn(startLink).getTrimmed().isEqual(range)) {
        return startLink;
      } else {
        return null;
      }
    }
  }
}

/**
 * @returns
 *   The parent element to evaluate whether an icon can be inserted as a child.
 */
function getParentElement(selection: DocumentSelection, model: Model): Element {
  const parent = findOptimalInsertionRange(selection, model).start.parent;
  if (parent.isEmpty && !parent.is('element', '$root'))
    return parent.parent as Element;
  return parent as Element;
}

/**
 * {@link module:link/linkui~findLinkElementAncestor Duplicates a private function in the `link` module.}
 */
function findLinkElementAncestor(position: ViewPosition): ViewAttributeElement | null {
  return position.getAncestors().find((ancestor): ancestor is ViewAttributeElement => isLinkElement(ancestor)) || null;
}
