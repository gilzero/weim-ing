import { Plugin } from 'ckeditor5/src/core';
import { setViewAttributes } from '@ckeditor/ckeditor5-html-support/src/utils';
import type { PluginInterface } from '@ckeditor/ckeditor5-core/src/plugin';
import type { DowncastAttributeEvent, DowncastWriter, Element as ModelElement, ViewElement, UpcastElementEvent } from 'ckeditor5/src/engine';
import { matchAllFAStyleClasses } from '../iconutils';

/**
 * Integration with the external `GeneralHtmlSupport` plugin. Preserves allowed
 * HTML attributes on the icon or link wrapping the icon.
 */
export default class IconGeneralHtmlSupport extends Plugin implements PluginInterface {
  /**
   * The plugin's name in the PluginCollection.
   */
  static get pluginName(): 'IconGeneralHtmlSupport' {
    return 'IconGeneralHtmlSupport' as const;
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const { plugins } = editor;

    if (!plugins.has('GeneralHtmlSupport') || !plugins.has('DataFilter')) {
      // Nothing to integrate with.
      return;
    }

    const { model, conversion } = editor;
    const { schema } = model;
    const dataFilter = plugins.get('DataFilter');

    schema.extend('icon', {
      allowAttributes: ['htmlLinkAttributes', 'htmlAttributes']
    });

    conversion.for('upcast').add(dispatcher => {
      dispatcher.on<UpcastElementEvent>('element:i',
        (_evt, data, conversionApi) => {
          const viewElement = data.viewItem;
          // Checks if the `i` element is actually an icon.
          if (!viewElement.getAttribute('class')?.match(matchAllFAStyleClasses)) {
            return;
          }
          const viewContainerElement = viewElement.parent;
          const preserveElementAttributes = (viewElement: ViewElement, attributeName: string) => {
            const viewAttributes = dataFilter.processViewAttributes(viewElement, conversionApi);
            if (viewAttributes) {
              conversionApi.writer.setAttribute(attributeName, viewAttributes, data.modelRange!);
            }
          };
          preserveElementAttributes(viewElement, 'htmlAttributes');
          if (viewContainerElement?.is('element', 'a')) {
            preserveElementAttributes(viewContainerElement, 'htmlLinkAttributes');
          }
        },
        { priority: 'low' }
      );
    });

    conversion.for('editingDowncast').add(dispatcher => {
      dispatcher.on<DowncastAttributeEvent<ModelElement>>('attribute:linkHref:icon',
        (_evt, data, conversionApi) => {
          if (!conversionApi.consumable.consume(data.item, 'attribute:htmlLinkAttributes:icon')) {
            return;
          }
          const modelElement = data.item;
          const containerElement = conversionApi.mapper.toViewElement(modelElement)!;
          const viewElement = getDescendantElement(conversionApi.writer, containerElement, 'a');
          if (viewElement) {
            setViewAttributes(conversionApi.writer, modelElement.getAttribute('htmlLinkAttributes')!, viewElement);
          }
        },
        { priority: 'low' }
      );
    });

    conversion.for('dataDowncast').add(dispatcher => {
      dispatcher.on<DowncastAttributeEvent<ModelElement>>('attribute:linkHref:icon',
        (_evt, data, conversionApi) => {
          if (!conversionApi.consumable.consume(data.item, 'attribute:htmlLinkAttributes:icon')) {
            return;
          }
          const modelElement = data.item;
          const viewElement = conversionApi.mapper.toViewElement(modelElement);
          const viewContainerElement = viewElement?.parent;
          if (viewContainerElement?.is('element', 'a')) {
            setViewAttributes(conversionApi.writer, modelElement.getAttribute('htmlLinkAttributes')!, viewContainerElement);
          }
        },
        { priority: 'low' }
      );
      dispatcher.on<DowncastAttributeEvent<ModelElement>>('attribute:htmlAttributes:icon',
        (evt, data, conversionApi) => {
          if (!conversionApi.consumable.consume(data.item, evt.name)) {
            return;
          }
          const modelElement = data.item;
          const viewElement = conversionApi.mapper.toViewElement(modelElement);
          if (viewElement?.is('element', 'i')) {
            setViewAttributes(conversionApi.writer, data.attributeNewValue!, viewElement);
          }
        },
        { priority: 'low' }
      );
    });
  }
}

/**
 * Gets descendant element from a container.
 *
 * @param writer
 *   The writer.
 * @param containerElement
 *   The container element.
 * @param elementName
 *   The element name.
 * @return
 *   The descendant element matching element name or undefined if not found.
 */
function getDescendantElement(writer: DowncastWriter, containerElement: ViewElement, elementName: string): ViewElement | undefined {
  const range = writer.createRangeOn(containerElement);
  for (const { item } of range.getWalker()) {
    if (item.is('element', elementName)) {
      return item;
    }
  }
  return;
}
