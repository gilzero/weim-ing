/**
 * @file contains the icon picker icon for FontAwesome icons.
 */

import { Collection, type Locale } from 'ckeditor5/src/utils';
import type { DropdownView, ListDropdownItemDefinition } from 'ckeditor5/src/ui';
import { View, addListToDropdown, createDropdown } from 'ckeditor5/src/ui';
import { UiViewModel as ViewModel } from '../iconshims';
import IconPickerFAIcon from './iconpickerfaicon';
import type { ChangeStyleEvent } from './iconpickerform';
import IconPickerForm from './iconpickerform';
import IconPickerSearch from './iconpickersearch';
import type { FontAwesomeStyle, FontAwesomeVersion, IconDefinition, IconName } from '../icontypes';
import { faStyleLabels } from '../iconconfig';
import HideableView from './hideableview';

export default class IconPickerFooter extends View {
  /**
   * The Font Awesome version.
   */
  private readonly faVersion: FontAwesomeVersion;

  /**
   * The name of the currently selected icon.
   * 
   * @observable
   */
  public declare iconName?: IconName | null;

  /**
   * The selected style of the currently selected icon.
   * 
   * @observable
   */
  public declare iconStyle?: FontAwesomeStyle;

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
  public declare styleFilter: FontAwesomeStyle | 'all';

  /**
   * The search form view.
   */
  public readonly searchView: IconPickerSearch;

  /**
   * The style filter dropdown view.
   */
  public readonly styleFilterView: DropdownView;

  /**
   * The selected icon preview view.
   */
  public readonly iconPreviewView: View;

  /**
   * The selected icon insert form view.
   */
  public readonly formView: IconPickerForm;

  /**
   * The FontAwesome icon view (if an icon is selected).
   */
  private faIcon: IconPickerFAIcon | null;

  /**
   * Constructs a new IconPickerFooter.
   * 
   * @param locale
   *   The locale.
   * @param faStyles
   *   The enabled Font Awesome icon styles.
   * @param faVersion
   *   The version of Font Awesome being used.
   */
  public constructor(locale: Locale, faVersion: FontAwesomeVersion, faStyles: FontAwesomeStyle[]) {
    super(locale);
    this.faVersion = faVersion;

    this.set('styleFilter', 'all');

    const t = locale.t, bind = this.bindTemplate;

    this.searchView = new IconPickerSearch(locale);
    this.searchView.delegate('search').to(this);

    this.styleFilterView = this._createStyleFilterDropdown(locale, faStyles);

    const styleFilterContainerView = new HideableView(locale, 'div', [this.styleFilterView]);
    styleFilterContainerView.bind('isVisible').to(this.styleFilterView.buttonView, 'isVisible');

    this.iconPreviewView = new View();
    this.iconPreviewView.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ckeditor5-icons__icon-preview']
      }
    });
    this.faIcon = null;

    this.formView = new IconPickerForm(locale);
    this.formView.delegate('changeStyle', 'cancel').to(this);
    this.formView.delegate('submit').to(this, 'execute');
    this.formView.bind('iconStyle').to(this);

    this.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck', 'ckeditor5-icons__picker-footer']
      },
      children: [
        {
          tag: 'div',
          attributes: {
            class: ['ck', bind.to('iconName', value => value ? '' : 'ck-hidden')]
          },
          children: [
            {
              tag: 'div',
              attributes: {
                class: ['ck', 'ckeditor5-icons__picker-preview']
              },
              children: [
                this.iconPreviewView,
                {
                  tag: 'div',
                  attributes: {
                    class: ['ck', 'ckeditor5-icons__icon-info']
                  },
                  children: [
                    {
                      tag: 'span',
                      attributes: {
                        class: ['ck', 'ckeditor5-icons__icon-label']
                      },
                      children: [{ text: bind.to('iconDefinition', value => value ? t(value.label) : '') }]
                    },
                    {
                      tag: 'span',
                      attributes: {
                        class: ['ck', 'ckeditor5-icons__icon-name']
                      },
                      children: [{ text: bind.to('iconName') }]
                    }
                  ]
                }
              ]
            },
            this.formView
          ]
        },
        {
          tag: 'div',
          attributes: {
            class: ['ck', bind.to('iconName', value => value ? 'ck-hidden' : '')]
          },
          children: [this.searchView, styleFilterContainerView]
        }
      ]
    });
  }

  /**
   * Refreshes the icon picker footer when an icon in the grid is selected.
   */
  public refresh() {
    if (this.iconDefinition)
      this.formView.refresh(this.iconName, this.iconDefinition);

    const iconPreviewView = this.iconPreviewView;
    let faIcon: IconPickerFAIcon | null = null;

    if (this.faIcon) {
      iconPreviewView.deregisterChild(this.faIcon);
      iconPreviewView.element!.innerText = '';
    }

    if (this.iconName && this.iconDefinition) {
      faIcon = new IconPickerFAIcon(this.locale!, this.faVersion, this.iconName, this.iconDefinition, this.iconStyle);
      iconPreviewView.registerChild(faIcon);
      iconPreviewView.element!.appendChild(faIcon.element!);
    }

    this.faIcon = faIcon;
  }

  /**
   * @param locale
   *   The locale.
   * @param faStyles
   *   The enabled Font Awesome icon styles.
   * @returns
   *   The style filter dropdown.
   */
  private _createStyleFilterDropdown(locale: Locale, faStyles: FontAwesomeStyle[]): DropdownView {
    const dropdownView = createDropdown(locale), defaultLabel = 'Select a style', t = locale.t;

    dropdownView.buttonView.set({
      label: t(defaultLabel),
      tooltip: t('Filter by style'),
      withText: true,
      class: 'ck-dropdown__button_label-width_auto'
    });
    dropdownView.buttonView.bind('label').to(this, 'styleFilter', value => value === 'all' ? t('All') : faStyleLabels[value as FontAwesomeStyle]);
    dropdownView.on('execute', eventInfo => {
      const styleFilter = eventInfo.source['name'] as FontAwesomeStyle | 'all';
      this.set('styleFilter', styleFilter);
      if (styleFilter !== 'all')
        this.formView.fire<ChangeStyleEvent>('changeStyle', styleFilter);
    });

    const items = new Collection<ListDropdownItemDefinition>();

    const model = new ViewModel({
      name: 'all',
      label: t('All'),
      withText: true
    });
    model.bind('isOn').to(this, 'styleFilter', value => value === 'all');
    items.add({ type: 'button', model });
    items.add({ type: 'separator' });

    for (const name of faStyles) {
      const model = new ViewModel({
        name,
        label: faStyleLabels[name],
        withText: true
      });
      model.bind('isOn').to(this, 'styleFilter', value => value === name);
      items.add({ type: 'button', model });
    }

    addListToDropdown(dropdownView, items);

    return dropdownView;
  }
}
