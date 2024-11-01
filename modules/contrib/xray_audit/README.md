# XRAY AUDIT

## HOW TO CONTRIBUTE A NEW REPORT

The module is based on plugins, to facilitate the development of new reports.
The developer can focus on generating the
report
and not on integrating it into the module.

## Plugins:

### XrayAuditGroupPlugin

These plugins generate a new entry on the module's main page (
“admin/reports/xray_audit”), where an index of the
different report categories available is displayed.

These plugins must extend the “XrayAuditGroupPluginBase” class and must be
created in the “src/Plugin/groups” folder.

#### Annotation parameters:

1. **id**: unique identifier of the plugin, used to build the url.

2. **label**: plugin label.

3. **description**: short description.

4. **sort**: the position in which to display on the page
   “admin/reports/xray_audit”.

Automatically, when we visit the main page of the module, the information
specified in the plugin annotations
and a link built from the plugin id are displayed.

This link will take us to a page where a list of all the "tasks" associated with
this plugin is shown.

```
/**
  * Plugin implementation of the xray_audit_group_plugin.
  *
  * @XrayAuditGroupPlugin (
  *   id = "displays",
  *   label = @Translation("Displays reports"),
  *   description = @Translation("Reports specifically about display data."),
  *   sort = 4
  * )
  */
```

### XrayAuditTaskPlugin

These plugins are always defined associated with an XrayAuditGroupPlugin. It is
the one that will contain the specific
code
that generates the reports.

They have to be defined inside the “src/Plugin/tasks” folder, and they have to
extend the “XrayAuditTaskPluginBase”
class.

#### Annotation parameters:

1. **id**: unique identifier of the plugin.

2. **label**: name of the group of reports.

3. **description**: short general description about the reports.

4. **group**: XrayAuditGroupPlugin to which it is associated.

5. **sort**: position in which it is displayed.

6. **operations**: an operation is what extracts the data and renders that data
   to build a report. An "
   XrayAuditTaskPlugin"
   can have multiple operations. Parameters of an operation:

    - **Index of the array**: unique identifier of the operation. This index is
      used to build the url of the report.

    - **"label"**: name of the operation, for example “Data
      architecture [Nodes]”.

    - **"description"**: short description about the operation or report.

    - **"dependencies"**: a list of modules on which the report depends. For
      example, if the report is going to extract
      data
      about the different paragraphs that exist in the installation, Paragraphs
      module must be active. If the dependency
      is not met,
      this operation is not displayed.

7. **dependencies**: it is also possible to define dependencies at the level of
   the XrayAuditTaskPlugin and not only of
   operations.

8. **install**: Name of the method that will be launched on module install.

9. **uninstall**: Name of the method that will be launched on module uninstall.

10. **batches**: Array of methods in the plugin that run batch process

    ```
   /**
     * Plugin implementation of queries_data_node.
     *
     * @XrayAuditTaskPlugin (
     *   id = "queries_data_media",
     *   label = @Translation("Data about medias"),
     *   description = @Translation("Queries execute on database to get reports
     *                  about medias."),
     *   group = "queries_data",
     *   sort = 3,
     *   operations = {
     *      "media_types" = {
     *          "label" = "Media types",
     *          "description" = "Media types.",
     *          "dependencies" = {"media"}
     *       },
     *    },
     *  batches = {"batch-parameter-name" = "method"}
     *  dependencies = {"media"},
     *  install = "method_name",
     *  uninstall = "method_name"
     *
     *   dependencies = {"media"},
     *   install = "method_name",
     *   uninstall = "method_name"
     * )
     */
    ```

#### Operations:

There are two main methods in this plugin that need to be overridden:

1. **“getDataOperationResult($operation)”** : this method is the one that will
   be in charge of executing the code that
   extracts
   the data in a specific operation. It will return an array with the data
   needed to generate the report.

    ```
    /**
     * {@inheritdoc}
     */
    public function getDataOperationResult(string $operation = '') {
      switch ($operation) {
        case 'node_by_types':
          return $this->nodeByTypes();
        case 'node_types_per_language_language':
          return $this->nodeTypesPerLanguage();
      }
      return [];
    }
   ```
2. **“buildDataRenderArray($data, $operation)”**: this method is responsible for
   building the render array built from
   the data returned by the “getDataOperationResult” method;

    ```
    /**
     * {@inheritdoc}
     */
    public function buildDataRenderArray(array $data, string $operation = '')
   {

      $description = '';
      $operation_data = $this->getOperations()[$operation] ?? NULL;
      if ($operation_data) {
        $description = $operation_data['description'] ?? '';
      }

      $header = $data['header_table'] ?? [];
      $rows = $data['results_table'] ?? [];

      $build = [];

      if ($description) {
        $build['#markup'] = '<p>' . $description . '</p>';
      }

      $build['table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      return $build;
    }
   ```

#### Batch
These processes are launched in a method defined in the plugin itself.
There is a parameter in the plugin announcement named "batches" where to
configure the batch process (see above).

The method of the batch process must return either a null or a
\Symfony\Component\HttpFoundation\RedirectResponse object.

When launching the batch process from a Controller, it is necessary
to use the "batch_process()" function and return a Redirect.
