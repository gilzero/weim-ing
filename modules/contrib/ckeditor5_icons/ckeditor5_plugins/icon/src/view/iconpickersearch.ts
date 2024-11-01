import type { InputViewInputEvent } from '@ckeditor/ckeditor5-ui/src/input/inputview';
import type { Locale } from 'ckeditor5/src/utils';
import type { ButtonView, InputTextView, ButtonExecuteEvent } from 'ckeditor5/src/ui';
import { icons } from 'ckeditor5/src/core';
import { createLabeledInputText, LabeledFieldView, View } from 'ckeditor5/src/ui';
import HideableView from './hideableview';
import { createButton } from './viewutils';

// cSpell:ignore inputview

/**
 * Defines the icon picker search form view.
 *
 * The search form contains the search text field and clear button.
 */
export default class IconPickerSearch extends View<HTMLFormElement> {

  /**
   * The labeled search field view.
   */
  public readonly searchFieldView: LabeledFieldView<InputTextView>;

  /**
   * The clear button view – clears the search when pressed.
   */
  public readonly clearButtonView: ButtonView;

  /**
   * The delay timer to prevent search spamming while typing into the search.
   */
  private delayTimer?: ReturnType<typeof setTimeout>;

  /**
   * Constructs a new IconPickerSearch.
   *
   * @param locale 
   *   The locale.
   */
  public constructor(locale: Locale) {
    super(locale);
    const t = locale.t;

    this.searchFieldView = new LabeledFieldView(locale, createLabeledInputText);
    this.searchFieldView.label = t('Search all icons');
    this.searchFieldView.fieldView.on<InputViewInputEvent>('input', () =>
      this._search(true, this.searchFieldView.fieldView.element?.value));

    this.clearButtonView = createButton(locale, t('Clear search'), icons.cancel, 'ck-button-cancel');
    this.clearButtonView.isVisible = false;
    this.clearButtonView.on<ButtonExecuteEvent>('execute', () => {
      this._search(false);
      this.searchFieldView.focus();
    });

    const searchClearButtonContainerView = new HideableView(locale, 'div', [this.clearButtonView]);
    searchClearButtonContainerView.bind('isVisible').to(this.clearButtonView, 'isVisible');

    this.setTemplate({
      tag: 'form',
      attributes: {
        class: ['ck', 'ckeditor5-icons__picker-search']
      },
      children: [this.searchFieldView, searchClearButtonContainerView]
    });
  }

  /**
   * @inheritdoc
   */
  public override render() {
    super.render();
    this.element!.addEventListener('submit', event => {
      // Allows immediate search on enter key press.
      event.preventDefault();
      this._search(false, this.searchFieldView.fieldView.element?.value);
    });
  }

  /**
   * Searches for something.
   *
   * @param delay
   *   Whether or not to delay (recommended to prevent search spamming on
   *   typing).
   * @param query
   *   The query to search for. Can be omitted to clear the search.
   */
  private _search(delay: boolean, query?: string) {
    const searchCallback = () => this.fire<SearchEvent>('search', query);
    if (this.delayTimer) {
      clearTimeout(this.delayTimer);
    }
    if (delay) {
      const delayMilliseconds = 500;
      this.delayTimer = setTimeout(searchCallback, delayMilliseconds);
    } else {
      searchCallback();
    }
    this.searchFieldView.fieldView.set('value', query);
  }

}

/**
 * The event fired when a search change is performed.
 */
export type SearchEvent = {
  name: 'search';
  args: [queryString?: string];
};

/**
 * The event fired when a search change is performed.
 */
export type SearchClearEvent = {
  name: 'searchClear';
  args: [];
};
