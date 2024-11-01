/**
 * @file contains the icon picker FormHeaderView.
 */
// cSpell:ignore svgs

import type { Locale } from 'ckeditor5/src/utils';
import { Collection } from 'ckeditor5/src/utils';
import type { ListDropdownItemDefinition, DropdownView, FocusableView } from 'ckeditor5/src/ui';
import { addListToDropdown, createDropdown, View } from 'ckeditor5/src/ui';
import { UiViewModel as ViewModel } from '../iconshims';
import type { CategoryDefinition, CategoryDefinitions, CategoryName, FontAwesomeStyle, FontAwesomeVersion, IconName } from '../icontypes';
import fontAwesomeIcon from 'fontawesome6/svgs/solid/font-awesome.svg';

export default class IconPickerHeader extends View implements FocusableView {
  /**
   * The name of the currently selected category.
   * 
   * @observable
   */
  public declare categoryName?: CategoryName;

  /**
   * The attribution name of the currently selected category.
   * 
   * @observable
   */
  public declare categoryAttributionName?: CategoryName | '_search';

  /**
   * The definition of the currently selected category.
   * 
   * @observable
   */
  public declare categoryDefinition?: CategoryDefinition;

  /**
   * The Font Awesome icon view.
   */
  private readonly attributionIconView: View;

  /**
   * The Font Awesome library attribution view.
   */
  public readonly attributionView: View;

  /**
   * The category dropdown view.
   */
  public readonly categoryDropdownView: DropdownView;

  /**
   * Constructs a new IconPickerView.
   * 
   * @param locale
   *   The locale.
   * @param faVersion
   *   The version of Font Awesome being used.
   * @param faCategories
   *   The Font Awesome category definitions.
   * @param faStyles
   *   The enabled Font Awesome icon styles.
   * @param recommendedIcons
   *   The icons to display in the recommended category.
   */
  public constructor(locale: Locale, faVersion: FontAwesomeVersion, faCategories: CategoryDefinitions, faStyles: FontAwesomeStyle[], recommendedIcons: IconName[] | null | undefined) {
    super(locale);

    const bind = this.bindTemplate, t = locale.t;

    this.categoryDropdownView = this._createCategoryDropdown(locale, faCategories, faStyles, recommendedIcons);
    this.categoryDropdownView.panelPosition = locale.uiLanguageDirection === 'rtl' ? 'se' : 'sw';

    this.attributionIconView = new View(locale);
    this.attributionIconView.setTemplate({ tag: 'span', attributes: { class: ['ck', bind.to('categoryAttributionName', (value => value === '_recommended' || value === '_search' ? 'ck-hidden' : ''))] } });
    this.attributionView = new View(locale);
    this.attributionView.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ckeditor5-icons__library-attr']
      },
      children: [
        this.attributionIconView,
        {
          'tag': 'span',
          children: [
            { text: bind.to('categoryAttributionName', (value => value === '_recommended' ? t('Recommended') :  value === '_search' ? t('Search') : (faVersion === '5' ? 'Font Awesome 5' : 'Font Awesome 6'))) }
          ]
        }
      ]
    });

    this.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ck-form__header', 'ckeditor5-icons__picker-header']
      },
      children: [
        {
          tag: 'div',
          children: [
            {
              tag: 'h2',
              attributes: {
                class: ['ck', 'ck-form__header__label']
              },
              children: [{ text: t('Icons') }]
            },
            this.attributionView
          ]
        },
        this.categoryDropdownView
      ]
    });

    this.on<CategorySelectionEvent>('execute', (_eventInfo, categoryName, categoryDefinition) => {
      this.set('categoryName', categoryName);
      this.set('categoryAttributionName', categoryName);
      this.set('categoryDefinition', categoryDefinition);
    });
  }

  /**
   * @inheritdoc
   */
  public override render() {
    super.render();
    this.attributionIconView.element!.innerHTML = fontAwesomeIcon;
  }

  /**
   * Focuses the `categoryDropdownView`.
   */
  public focus() {
    this.categoryDropdownView.focus();
  }

  /**
   * @param locale
   *   The locale.
   * @param faCategories
   *   The object containing the category definitions.
   * @param faStyles
   *   The enabled Font Awesome icon styles.
   * @param recommendedIcons
   *   The icons to display in the recommended category.
   * @returns
   *   The category selection dropdown.
   */
  private _createCategoryDropdown(locale: Locale, faCategories: CategoryDefinitions, faStyles: FontAwesomeStyle[], recommendedIcons: IconName[] | null | undefined): DropdownView {
    const dropdownView = createDropdown(locale), items = this._createCategoryDropdownItems(locale, faCategories, faStyles, recommendedIcons), defaultLabel = 'Select a category', t = locale.t;

    dropdownView.buttonView.set({
      label: t(defaultLabel),
      tooltip: t('Icon categories'),
      withText: true,
      class: 'ck-dropdown__button_label-width_auto'
    });
    dropdownView.buttonView.bind('label').to(this, 'categoryDefinition', value => t(value ? value.label : defaultLabel));
    dropdownView.panelView.extendTemplate({ attributes: { tabindex: '-1' } }); // Prevents grabbing the scrollbar from closing the panel.
    dropdownView.on('execute', eventInfo => {
      const categoryName = (eventInfo.source as typeof ViewModel)['name'] as string;
      this.fire<CategorySelectionEvent>('execute', categoryName, faCategories[categoryName]!);
    });

    addListToDropdown(dropdownView, items);

    return dropdownView;
  }

  /**
   * @returns
   *   The category dropdown view items collection.
   */
  private _createCategoryDropdownItems(locale: Locale, faCategories: CategoryDefinitions, faStyles: FontAwesomeStyle[], recommendedIcons: IconName[] | null | undefined): Collection<ListDropdownItemDefinition> {
    const items = new Collection<ListDropdownItemDefinition>();

    const pinnedCategoryNames: CategoryName[] = [];
    const pinnedCategoryDefinitions: CategoryDefinitions = {
      all: { icons: [], label: 'All' },
      brands: { icons: [], label: 'Brands' }
    };

    if (recommendedIcons) { // Adds the "Recommended" category if it is defined.
      const recommendedCategoryDefinition = faCategories['_recommended'] = { icons: recommendedIcons, label: 'Recommended' };
      this._addCategoryDropdownItem(locale, items, '_recommended', recommendedCategoryDefinition);
      items.add({ type: 'separator' });
    }

    pinnedCategoryNames.push('all');
    if (faStyles.includes('brands')) // Adds the "Brands" category if the brands style is accessible.
      pinnedCategoryNames.push('brands');

    const categoryEntries = Object.entries<CategoryDefinition>(faCategories);

    for (const categoryName of pinnedCategoryNames) {
      const categoryDefinition = pinnedCategoryDefinitions[categoryName]!, categoryNameEscaped = '_' + categoryName;
      this._addCategoryDropdownItem(locale, items, categoryNameEscaped, categoryDefinition);
      faCategories[categoryNameEscaped] = categoryDefinition;
    }
    items.add({ type: 'separator' });
    for (const [categoryName, categoryDefinition] of categoryEntries) {
      if ('_' !== categoryName[0])
        this._addCategoryDropdownItem(locale, items, categoryName, categoryDefinition);
    }

    return items;
  }

  /**
   * Adds a new item to the category dropdown view items collection.
   */
  private _addCategoryDropdownItem(locale: Locale, items: Collection<ListDropdownItemDefinition>, categoryName: CategoryName, categoryDefinition: CategoryDefinition) {
    const model = new ViewModel({
      name: categoryName,
      label: locale.t(categoryDefinition.label),
      withText: true
    });
    model.bind('isOn').to(this, 'categoryName', value => value === categoryName);
    items.add({ type: 'button', model });
  }
}

/**
 * The event fired when a category is selected in the category dropdown.
 */
export type CategorySelectionEvent = {
  name: 'execute';
  args: [categoryName: CategoryName, categoryDefinition: CategoryDefinition];
};
