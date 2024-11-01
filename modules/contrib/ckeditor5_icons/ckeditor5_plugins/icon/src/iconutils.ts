/** 
 * @file defines helper functions for the icon plugin.
 */

import type { Element as ModelElement, ViewElement, DocumentSelection, ViewDocumentSelection } from 'ckeditor5/src/engine';
import type { FontAwesomeVersion, FontAwesomeStyle, IconDefinition, CategoryDefinitions, IconName, IconDefinitionAlt, IconDefinitions } from './icontypes';
import { faStyleClassByVersion, faStyleLabels } from './iconconfig';
import type { Editor } from 'ckeditor5/src/core';

let faCategories: CategoryDefinitions | undefined, faIcons: IconDefinitions | undefined, faStyles: FontAwesomeStyle[] | undefined;

/**
 * @param selection 
 *   The selection.
 * @returns
 *   The selected icon widget, or null if there isn't one.
 */
export function getSelectedIconWidget(selection: DocumentSelection): ModelElement | null {
  const selectedElement = selection.getSelectedElement();

  if (selectedElement && selectedElement.is('element') && isIconWidget(selectedElement))
    return selectedElement;

  return null;
}

/**
 * @param selection 
 *   The selection.
 * @returns
 *   The selected icon view element, or null if there isn't one.
 */
export function getSelectedIconViewElement(selection: ViewDocumentSelection): ViewElement | null {
  const selectedElement = selection.getSelectedElement();
  return selectedElement && selectedElement.is('element') && selectedElement.hasClass('ckeditor5-icons__widget') ? selectedElement : null;
}

/**
 * @returns
 *   `true` if the metadata has been loaded, `false` if not.
 */
export function metadataLoaded(): boolean {
  return !!(faCategories && faIcons && faStyles);
}

/**
 * Gets all of the Font Awesome metadata and enabled styles. The metadata may need to be loaded asynchronously from an external URI.
 * 
 * @param editor
 *   The editor containing the icon plugin configuration.
 * @returns
 *   An object containing the Font Awesome categories, icons, and styles available to the icon plugin.
 */
export async function getFontAwesomeMetadata(editor: Editor): Promise<{ categories: CategoryDefinitions, icons: IconDefinitions, styles: FontAwesomeStyle[] }> {
  const config = editor.config;

  if (faCategories && faIcons && faStyles)
    return { categories: faCategories, icons: faIcons, styles: faStyles };

  const faCategoriesConfig: CategoryDefinitions | undefined = config.get('icon.faCategories');
  const faIconsConfig: Record<IconName, IconDefinition | IconDefinitionAlt> | undefined = config.get('icon.faIcons');
  faStyles = config.get('icon.faStyles') || Object.keys(faStyleLabels) as FontAwesomeStyle[];

  if (faCategoriesConfig && faIconsConfig)
    return processFontAwesomeMetadata(faCategoriesConfig, faIconsConfig, faStyles);

  const asyncMetadataURI = config.get('icon.asyncMetadataURI')!, response = await fetch(asyncMetadataURI), json = await response.json();
  return processFontAwesomeMetadata(json.categories, json.icons, faStyles);
}

/**
 * Processes the Font Awesome metadata by dropping non-enabled styles and converting IconDefinitionAlts into IconDefinitions if needed.
 * 
 * @param categories
 * @param icons
 * @param styles
 * @returns
 *   An object containing the processed Font Awesome categories and icons, and passthrough of styles.
 */
function processFontAwesomeMetadata(categories: CategoryDefinitions, icons: Record<IconName, IconDefinition | IconDefinitionAlt>, styles: FontAwesomeStyle[]): { categories: CategoryDefinitions, icons: IconDefinitions, styles: FontAwesomeStyle[] } {
  for (const [iconName, iconDefinition] of Object.entries(icons)) {
    iconDefinition.styles = iconDefinition.styles.filter(value => styles.includes(value)); // Drops any non-allowed styles from icons.
    if (!iconDefinition.styles.length) { // Drops icons with no styles.
      delete icons[iconName];
      continue;
    }
    if ((iconDefinition as any).search_terms !== undefined){ // Trims the search terms if custom metadata is being used. For the default metadata this is taken care of already.
      (iconDefinition as IconDefinition).search = { terms: (iconDefinition as IconDefinitionAlt).search_terms.map(term => ('' + term).trim()) };
      delete (iconDefinition as any).search_terms;
    }
  }

  for (const [categoryName, categoryDefinition] of Object.entries(categories)) {
    categoryDefinition.icons = categoryDefinition.icons.filter(value => !!icons[value]); // Drops icons that don't exist from the categories.
    if (!categoryDefinition.icons.length) // Drops categories with no icons.
      delete categories[categoryName];
  }

  faCategories = categories;
  faIcons = icons as IconDefinitions;

  return { categories: faCategories, icons: faIcons, styles: styles };
}

/**
 * @param iconDefinition 
 * @param iconStyle
 * @returns {FontAwesomeStyle}
 *   A valid icon style for this icon definition.
 *   If `null` or an invalid value is supplied to `iconStyle`, the first style supported by the icon will be returned.
 */
export function getValidIconStyle(iconDefinition: IconDefinition, iconStyle?: FontAwesomeStyle | null): FontAwesomeStyle {
  const styles = iconDefinition.styles;

  let styleIndex = 0;
  if (iconStyle) {
    const _styleIndex = styles.indexOf(iconStyle);
    if (_styleIndex !== -1)
      styleIndex = _styleIndex;
  }

  return styles[styleIndex]!;
}

/**
 * Gets all Font Awesome CSS classes.
 *
 * @returns
 *   CSS class → FontAwesomeStyle mapping for every Font Awesome style CSS
 *   class.
 */
function getAllFAStyleClasses() {
  // Defines the values for detecting an icon's style.
  const faStyles = Object.keys(faStyleLabels) as (keyof typeof faStyleLabels)[], styleClassNames: Record<string, FontAwesomeStyle> = {};
  faStyles.forEach(value => {
    const classesForStyle = getFAStyleClasses(value);
    classesForStyle.forEach((className: string) => styleClassNames[className] = value);
  });
  return styleClassNames;
}

/**
 * CSS class → FontAwesomeStyle mapping for every Font Awesome style CSS class.
 */
export const allFAStyleClasses = getAllFAStyleClasses();

/**
 * A regular expression to match all Font Awesome style classes.
 */
export const matchAllFAStyleClasses = new RegExp(Object.keys(allFAStyleClasses).join('|'));

/**
 * Gets a CSS class for a specific Font Awesome version and icon style.
 *
 * @param faVersion
 *   A Font Awesome version.
 * @param iconStyle
 *   A Font Awesome icon style.
 * @returns
 *   The correct style class for the specified Font Awesome version and icon style.
 */
export function getFAStyleClass(faVersion: FontAwesomeVersion, iconStyle: FontAwesomeStyle): string {
  return faStyleClassByVersion[faVersion][iconStyle] || 'fas';
}

/**
 * Gets all CSS classes associated with a Font Awesome icon style.
 *
 * @param iconStyle
 *   A Font Awesome icon style.
 * @returns
 *   All the style classes for the specified Font Awesome icon style.
 */
function getFAStyleClasses(iconStyle: FontAwesomeStyle): string[] {
  const faVersions = Object.keys(faStyleClassByVersion), values: string[] = [];
  for (const faVersion of faVersions) {
    const className = faStyleClassByVersion[faVersion][iconStyle];
    if (className)
      values.push(className)
  }
  return values;
}

/**
 * Checks if a model element is an icon widget.
 *
 * @param element 
 *   The model element.
 * @returns
 *   Whether the element is a icon widget.
 */
function isIconWidget(element: ModelElement): boolean {
  return element.name === 'icon';
}
