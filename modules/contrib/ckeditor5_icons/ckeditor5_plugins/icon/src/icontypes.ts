/**
 * @file defines general types used by the icon plugin.
 */

export type FontAwesomeVersion = '5' | '6';
export type FontAwesomeStyle = 'solid' | 'regular' | 'light' | 'thin' | 'duotone' | 'brands' | 'custom';

export type CategoryName = string;

export type CategoryDefinition = {
  label: string;
  icons: IconName[];
};

export type CategoryDefinitions = Record<CategoryName, CategoryDefinition>;

export type IconName = string;

export type IconDefinition = {
  label: string;
  styles: FontAwesomeStyle[];
  search: {
    terms: string[];
  };
};

export type IconDefinitionAlt = {
  name: IconName;
  type: 'solid' | 'brands';
  label: string;
  styles: FontAwesomeStyle[];
  search_terms: (string | number)[];
};

export type IconDefinitions = Record<IconName, IconDefinition>;

export type SelectableOption = {
  label: string;
  icon?: string;
  className?: string;
  compatibility?: FontAwesomeVersion[];
};
