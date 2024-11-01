/**
 * @file defines configuration for the icon plugin.
 */

import type { FontAwesomeVersion, FontAwesomeStyle, SelectableOption, CategoryDefinitions, IconName, IconDefinition, IconDefinitionAlt } from './icontypes';
import type { ToolbarConfigItem } from 'ckeditor5/src/core';
import { icons } from 'ckeditor5/src/core';
import objectSizeExtraSmall from '../../../icons/object-size-extra-small.svg';

/**
 * The options available in `editor.config.get('icon')`.
 */
export interface IconConfig {
  toolbarItems: ToolbarConfigItem[];
  faVersion: FontAwesomeVersion;
  faCategories?: CategoryDefinitions;
  faStyles?: FontAwesomeStyle[];
  faIcons?: Record<IconName, IconDefinition | IconDefinitionAlt>;
  recommendedIcons?: IconName[] | null;
  customMetadata?: boolean;
  asyncMetadataURI: string;
};

export type ModelAttribute = 'iconFA' | 'iconStyle' | 'iconSize' | 'iconAlignment';

export type ModelAttributeDefinition<T extends string = string, A extends ModelAttribute = ModelAttribute> = [T, A];

// === Sizes ===
export type Size = 'extraSmall' | 'small' | 'regular' | 'large' | 'extraLarge' | '2x' | '3x' | '4x' | '5x' | '6x' | '7x' | '8x' | '9x' | '10x';
export type SizeAttributeDefinition = ModelAttributeDefinition<Size, 'iconSize'>;
export const sizeOptions: { [key in Size]: SelectableOption; } = {
  extraSmall: {
    label: 'Extra Small',
    icon: objectSizeExtraSmall,
    className: 'fa-xs'
  },
  small: {
    label: 'Small',
    icon: icons.objectSizeSmall,
    className: 'fa-sm'
  },
  regular: {
    label: 'Regular',
    icon: icons.objectSizeMedium
  },
  large: {
    label: 'Large',
    icon: icons.objectSizeLarge,
    className: 'fa-lg'
  },
  extraLarge: {
    label: 'Extra Large',
    icon: icons.objectSizeFull,
    className: 'fa-xl',
    compatibility: ['6']
  },
  '2x': {
    label: '2x',
    className: 'fa-2x'
  },
  '3x': {
    label: '3x',
    className: 'fa-3x'
  },
  '4x': {
    label: '4x',
    className: 'fa-4x'
  },
  '5x': {
    label: '5x',
    className: 'fa-5x'
  },
  '6x': {
    label: '6x',
    className: 'fa-6x'
  },
  '7x': {
    label: '7x',
    className: 'fa-7x'
  },
  '8x': {
    label: '8x',
    className: 'fa-8x'
  },
  '9x': {
    label: '9x',
    className: 'fa-9x'
  },
  '10x': {
    label: '10x',
    className: 'fa-10x'
  }
};
export const sizeDefault: Size = 'regular';


// === Alignments ===
export type Alignment = 'none' | 'left' | 'right';
export type AlignmentAttributeDefinition = ModelAttributeDefinition<Alignment, 'iconAlignment'>;
export const alignmentOptions: { [key in Alignment]: SelectableOption; } = {
  none: {
    label: 'With text',
    icon: icons.objectCenter
  },
  left: {
    label: 'Pull left',
    icon: icons.objectLeft,
    className: 'fa-pull-left'
  },
  right: {
    label: 'Pull right',
    icon: icons.objectRight,
    className: 'fa-pull-right'
  }
};
export const alignmentDefault: Alignment = 'none';

// === Styles ===
export type StyleAttributeDefinition = ModelAttributeDefinition<FontAwesomeStyle, 'iconStyle'>;

/**
 * Labels for all the style options.
 */
export const faStyleLabels: { [key in FontAwesomeStyle]: string; } = {
  solid: 'Solid',
  regular: 'Regular',
  light: 'Light',
  thin: 'Thin',
  duotone: 'Duotone',
  brands: 'Brands',
  custom: 'Custom'
};

export const styleDefault = Object.keys(faStyleLabels)[0] as FontAwesomeStyle;

/**
 * A definition of Font Awesome style classes by version.
 */
export const faStyleClassByVersion: { [key in FontAwesomeVersion]: { [key in FontAwesomeStyle]?: string; } } = {
  '6': {
    solid: 'fa-solid',
    regular: 'fa-regular',
    light: 'fa-light',
    thin: 'fa-thin',
    duotone: 'fa-duotone',
    brands: 'fa-brands',
    custom: 'fa-kit'
  },
  '5': {
    solid: 'fas',
    regular: 'far',
    light: 'fal',
    duotone: 'fad',
    brands: 'fab',
    custom: 'fak'
  }
};
