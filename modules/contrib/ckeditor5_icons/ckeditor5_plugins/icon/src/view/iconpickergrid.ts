/**
 * @file contains the icon picker grid view.
 */
// cSpell:ignore addkeyboardhandlingforgrid charactergridview

import type { Locale } from 'ckeditor5/src/utils';
import type { ObservableChangeEvent } from 'ckeditor5/src/utils';
import { Collection, CollectionChangeEvent, FocusTracker, KeystrokeHandler } from 'ckeditor5/src/utils';
import type { ButtonExecuteEvent, DropdownView, FocusableView, ListDropdownItemDefinition, ViewCollection } from 'ckeditor5/src/ui';
import { View, createDropdown } from 'ckeditor5/src/ui';
import { UiViewModel as ViewModel } from '../iconshims';
import addKeyboardHandlingForGrid from '@ckeditor/ckeditor5-ui/src/bindings/addkeyboardhandlingforgrid';
import IconPickerItem from './iconpickeritem';
import { ButtonView, addListToDropdown } from 'ckeditor5/src/ui';
import type { FontAwesomeStyle, FontAwesomeVersion, CategoryDefinition, CategoryName, IconDefinition, IconDefinitions, IconName } from '../icontypes';
import HideableView from './hideableview';
import { faStyleLabels } from '../iconconfig';

export default class IconPickerGrid extends View implements FocusableView {
  /**
   * The Font Awesome version.
   */
  private readonly faVersion: FontAwesomeVersion;

  /**
   * The name of the currently selected category.
   * 
   * @observable
   */
  public declare categoryName?: CategoryName | null;

  /**
   * The definition of the currently selected category.
   * 
   * @observable
   */
  public declare categoryDefinition?: CategoryDefinition | null;

  /**
   * The name of the currently selected icon.
   * 
   * @observable
   */
  public declare iconName?: IconName | null;

  /**
   * The definition of the currently selected icon.
   * 
   * @observable
   */
  public declare iconDefinition?: IconDefinition | null;

  /**
   * The style to filter icons in the grid by.
   * 
   * @observable
   */
  public declare styleFilter?: FontAwesomeStyle | 'all';

  /**
   * The value of the "All" filter.
   * 
   * @observable
   */
  public declare allCategoryFilter: string;

  /**
   * The "All" category filter dropdown.
   */
  public readonly allCategoryFilterView: DropdownView;

  /**
   * The "All" category filter dropdown items.
   */
  private allCategoryFilterViewItems?: Collection<ListDropdownItemDefinition>;

  /**
   * The view containing the grid's sections.
   */
  public readonly itemsView: View;

  /**
   * The fallback view if there are no items in the search.
   */
  public readonly fallbackView: HideableView;

  /**
   * The "Expand" button view.
   */
  public readonly expandButtonView: ButtonView;

  /**
   * The items for which to track focus (arrow keys).
   */
  private readonly items: ViewCollection<IconPickerItem>;

  /**
   * The sections (larger categories may contain multiple sections).
   */
  private readonly sections: ViewCollection<View>;

  /**
   * The focus tracker.
   */
  private readonly focusTracker: FocusTracker;

  /**
   * The keystroke handler.
   */
  private readonly keystrokes: KeystrokeHandler;

  /**
   * Constructs a new IconPickerGrid.
   * 
   * @param locale
   *   The locale.
   * @param faVersion
   *   The version of Font Awesome being used.
   */
  public constructor(locale: Locale, faVersion: FontAwesomeVersion) {
    super(locale);
    this.faVersion = faVersion;

    this.set('allCategoryFilter', 'a');

    const bind = this.bindTemplate, t = locale.t;

    this.items = this.createCollection();
    this.sections = this.createCollection();

    this.allCategoryFilterView = this._createAllCategoryFilterDropdown(locale);

    this.itemsView = new View(locale);
    this.itemsView.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ckeditor5-icons__grid-section']
      }
    });

    this.fallbackView = new HideableView(locale, 'div', [{ text: bind.to('styleFilter', value => !value || value === 'all' ? t('No icons match your search.') : t('No icons in the %0 style match your search.', [faStyleLabels[value as FontAwesomeStyle]])) }]);
    this.fallbackView.extendTemplate({
      attributes: {
        class: 'ckeditor5-icons__grid-fallback'
      }
    });

    this.expandButtonView = new ButtonView(locale);
    this.expandButtonView.set({
      icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M.941 4.523a.75.75 0 1 1 1.06-1.06l3.006 3.005 3.005-3.005a.75.75 0 1 1 1.06 1.06l-3.549 3.55a.75.75 0 0 1-1.168-.136L.941 4.523z"></path></svg>',
      label: t('Expand'),
      tooltip: t('Show more icons'),
      withText: true,
      isVisible: false,
      class: 'ckeditor5-icons__grid-expand'
    });

    this.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ckeditor5-icons__grid']
      },
      children: [
        {
          tag: 'div',
          attributes: {
            class: ['ck', 'ckeditor5-icons__grid-options', 'ckeditor5-icons__grid-options-top', this.allCategoryFilterView.buttonView.bindTemplate.to('isVisible', value => value ? '' : 'ck-hidden')]
          },
          children: [this.allCategoryFilterView]
        },
        {
          tag: 'div',
          attributes: {
            class: ['ck', 'ckeditor5-icons__grid-scrollable']
          },
          children: [
            this.fallbackView,
            this.itemsView,
            {
              tag: 'div',
              attributes: {
                class: ['ck', 'ckeditor5-icons__grid-options', 'ckeditor5-icons__grid-options-bottom', this.expandButtonView.bindTemplate.to('isVisible', value => value ? '' : 'ck-hidden')]
              },
              children: [this.expandButtonView]
            }
          ]
        }
      ]
    });

    this.focusTracker = new FocusTracker();
    this.keystrokes = new KeystrokeHandler();

    // Enables wrap-around arrow key navigation of the grid. Requires `@ckeditor/ckeditor5-ui` package.
    // See `ckeditor5/packages/ckeditor5-special-characters/src/ui/charactergridview.ts`.
    addKeyboardHandlingForGrid({
      keystrokeHandler: this.keystrokes,
      focusTracker: this.focusTracker,
      gridItems: this.items,
      numberOfColumns: () => global.window
        .getComputedStyle(this.itemsView.element!.firstChild as Element) // Responsive .ck-character-grid__tiles
        .getPropertyValue('grid-template-columns')
        .split(' ')
        .length,
      uiLanguageDirection: locale.uiLanguageDirection
    });
  }

  /**
   * @returns
   *   An IconPickerItem created based on the provided icon.
   */
  private _createItem(iconName: IconName, iconDefinition: IconDefinition): IconPickerItem {
    const item = new IconPickerItem(this.locale!, this.faVersion, iconName, iconDefinition, !this.styleFilter || this.styleFilter === 'all' ? null : this.styleFilter as FontAwesomeStyle);

    // Attaches events to the grid item.
    item.on('mouseover', () => this.fire<IconHoverEvent>('itemHover', iconName, iconDefinition));
    item.on('focus', () => this.fire<IconFocusEvent>('itemFocus', iconName, iconDefinition));
    item.on('execute', () => this.fire<IconSelectionEvent>('execute', iconName, iconDefinition));
    item.bind('isOn').to(this, 'iconName', value => iconName === value);

    return item;
  }

  /**
   * Refreshes this icon picker grid based on a category selection.
   */
  public refresh(iconDefinitions: IconDefinitions, categoryName?: CategoryName | null, categoryDefinition?: CategoryDefinition | null, searchQuery?: string | null) {
    const categoryName_ = categoryName || this.categoryName, categoryDefinition_ = categoryDefinition || this.categoryDefinition;

    this.items.clear();
    this.itemsView.deregisterChild(this.sections);
    if (this.itemsView.element)
      this.itemsView.element.innerText = '';
    this.sections.clear();

    let iconNames: IconName[];
    if (categoryName_ === '_all')
      iconNames = Object.keys(iconDefinitions);
    else if (categoryName_ === '_brands')
      iconNames = Object.keys(iconDefinitions).filter(value => iconDefinitions[value]!.styles.includes('brands'));
    else if (categoryDefinition_)
      iconNames = categoryDefinition_.icons;
    else iconNames = [];

    if (searchQuery)
      iconNames = searchResults(iconNames, iconDefinitions, searchQuery);
    if (!searchQuery && categoryName_ === '_all') { // Enables the alphabetical filtering of the "All" category.
      const allCategoryFilter = this.allCategoryFilter;
      iconNames = allCategoryFilter === '#' ? iconNames.filter(iconName => '0123456789'.includes(iconName[0]!)) : iconNames.filter(iconName => iconName[0] === allCategoryFilter[0]);
      if (!this.allCategoryFilterViewItems) {
        this.allCategoryFilterViewItems = this._createAllCategoryFilterDropdownItems();
        addListToDropdown(this.allCategoryFilterView, this.allCategoryFilterViewItems);
        this.on<ObservableChangeEvent>('change:allCategoryFilter', () => {
          this.refresh(iconDefinitions);
          this.fire<IconSelectionEvent>('execute', null, null);
        });
      }
      this.allCategoryFilterView.buttonView.isVisible = true;
    } else this.allCategoryFilterView.buttonView.isVisible = false;

    if (categoryName_ !== '_brands' && this.styleFilter && this.styleFilter !== 'all') // Enables filtering by style.
      iconNames = iconNames.filter(iconName => iconDefinitions[iconName]?.styles.includes(this.styleFilter! as FontAwesomeStyle));

    if (iconNames.length === 0) {
      this.fallbackView.isVisible = true;
      this.expandButtonView.isVisible = false;
      this.fire<GridSectionLoadEvent>('gridSectionLoad', false, false);
    } else {
      this.fallbackView.isVisible = false;
      this._populateGrid(iconNames, iconDefinitions);
    }
  }

  /**
   * @inheritdoc
   */
  public override render() {
    super.render();

    // If `render` hasn't been called yet, there should only be one section.
    // Adds the items to the section and allows them to be focused.
    const section = this.sections.get(0);
    if (section) {
      for (const item of this.items) {
        const element = item.element!;
        section.element!.appendChild(element);
        this.focusTracker.add(element);
      }
      this.itemsView.element!.appendChild(section.element!);
    }

    // Allows the focus tracker to update automatically when items are added or removed as a result of calls to `_populateGrid`.
    this.items.on<CollectionChangeEvent<IconPickerItem>>('change', (_eventInfo, { added, removed }) => {
      for (const item of added)
        this.focusTracker.add(item.element!);
      for (const item of removed)
        this.focusTracker.remove(item.element!);
    });

    this.keystrokes.listenTo(this.element!);
  }

  /**
   * @inheritdoc
   */
  public override destroy() {
    super.destroy();

    this.focusTracker.destroy();
    this.keystrokes.destroy();
  }

  /**
   * Focuses a focusable in `items`.
   */
  public focus() {
    if (this.iconName) {
      const item = this.items.find(item => item.isOn);
      if (item) {
        item.focus();
        return;
      }
    }
    const first = this.items.first;
    if (first)
      first.focus();
  }

  /**
   * Populates the icon grid.
   */
  private _populateGrid(iconNames: IconName[], iconDefinitions: IconDefinitions, startAt = 0) {
    const buttonView = this.expandButtonView;
    this.stopListening(buttonView, 'execute');

    const max = 200, length = iconNames.length - startAt, section = new View(), sectionItems: ViewCollection<IconPickerItem> = this.createCollection();

    for (let index = 0; index < Math.min(max, length); index++) {
      const iconName = iconNames[startAt + index]!, iconDefinition = iconDefinitions[iconName];
      if (iconDefinition) {
        const item = this._createItem(iconName, iconDefinitions[iconName]!);
        sectionItems.add(item);
        this.items.add(item);
      }
    }

    section.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ckeditor5-icons__grid-items']
      },
      children: sectionItems
    });

    this.sections.add(section);
    this.itemsView.registerChild(section);
    if (this.itemsView.element)
      this.itemsView.element.appendChild(section.element!);

    if (length > max) {
      buttonView.isVisible = true;
      this.listenTo<ButtonExecuteEvent>(buttonView, 'execute', () => {
        this.items.last!.focus();
        this._populateGrid(iconNames, iconDefinitions, startAt + max);
      });
      this.fire<GridSectionLoadEvent>('gridSectionLoad', !this.fallbackView.isVisible, true);
    } else {
      buttonView.isVisible = false;
      this.fire<GridSectionLoadEvent>('gridSectionLoad', !this.fallbackView.isVisible, false);
    }
  }

  /**
   * @param locale
   *   The locale.
   * @returns
   *   The "all" category filter dropdown view.
   */
  private _createAllCategoryFilterDropdown(locale: Locale): DropdownView {
    const dropdownView = createDropdown(locale), t = locale.t;

    dropdownView.buttonView.set({
      tooltip: t('Filter All'),
      withText: true,
      isVisible: false,
      class: 'ck-dropdown__button_label-width_auto'
    });

    dropdownView.buttonView.bind('label').to(this, 'allCategoryFilter', value => value.toUpperCase());
    dropdownView.panelView.extendTemplate({ attributes: { tabindex: '-1' } }); // Prevents grabbing the scrollbar from closing the panel.
    dropdownView.on('execute', eventInfo => this.set('allCategoryFilter', eventInfo.source['name'] as string));

    return dropdownView;
  }

  /**
   * @returns
   *   The "all" category filter dropdown items.
   */
  private _createAllCategoryFilterDropdownItems(): Collection<ListDropdownItemDefinition> {
    const items = new Collection<ListDropdownItemDefinition>();

    for (const name of '#abcdefghijklmnopqrstuvwxyz') {
      const model = new ViewModel({
        name,
        label: name.toUpperCase(),
        withText: true
      });
      model.bind('isOn').to(this, 'allCategoryFilter', value => value === name);
      items.add({ type: 'button', model });
    }

    return items;
  }
}

/**
 * @returns
 *   The filtered search results.
 */
function searchResults(iconNames: IconName[], iconDefinitions: IconDefinitions, searchQuery: string): IconName[] {
  searchQuery = searchQuery.toLowerCase();

  if (searchQuery.length > 3 && searchQuery.substring(0, 3) === 'fa-') // Strips `fa-` prefix.
    searchQuery = searchQuery.substring(3);

  const orderedResults: IconName[] = [], resultSet = new Set<IconName>();

  if (iconNames.includes(searchQuery)) { // First pass: checks exact match of icon name.
    orderedResults.push(searchQuery);
    resultSet.add(searchQuery);
  }

  for (const iconName of iconNames) { // Second pass: checks exact match of keywords.
    if (iconName !== searchQuery && iconDefinitions[iconName]!.search.terms.includes(searchQuery)) {
      orderedResults.push(iconName);
      resultSet.add(iconName);
    }
  }

  for (const iconName of iconNames) { // Third pass: checks icon name starts with.
    if (!resultSet.has(iconName) && iconName.indexOf(searchQuery) === 0) {
      orderedResults.push(iconName);
      resultSet.add(iconName);
    }
  }

  return orderedResults;
}

/**
 * The event fired when an icon is selected.
 */
export type IconSelectionEvent = {
  name: 'execute';
  args: [iconName: IconName | null, IconDefinition: IconDefinition | null];
};

/**
 * The event fired when an icon is hovered over.
 */
export type IconHoverEvent = {
  name: 'itemHover';
  args: [iconName: IconName, IconDefinition: IconDefinition];
};

/**
 * The event fired when an icon is focused.
 */
export type IconFocusEvent = {
  name: 'itemFocus';
  args: [iconName: IconName, IconDefinition: IconDefinition];
};

/**
 * The event fired when the grid has loaded a new section of icons.
 */
export type GridSectionLoadEvent = {
  name: 'gridSectionLoad';
  args: [gridFocusable: boolean, expandButtonVisible: boolean];
};
