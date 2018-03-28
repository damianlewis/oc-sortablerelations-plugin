# OctoberCMS Sortable Relations Plugin
Adds drag and drop sorting functionality to the view list of the relations controller in October CMS.

## Usage
The following example illustrates how to use this plugin. It shows a many-to-many relationship between a category and it's related products. 

Add a `relation_sort_order` field to the pivot database table.

```php
class CreateCategoryProductTable extends Migration
{
    public function up()
    {
        Schema::create('acme_plugin_category_product', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('category_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('relation_sort_order')->unsigned();
            $table->primary(['category_id', 'product_id'], 'category_product_primary');
        });
    }

    ...
}
```

Use the `DamianLewis\SortableRelations\Traits\SortableRelations` trait within the parent model of the relation. Add the sortable relation to the `$sortableRelations` array and add the `'relation_sort_order'` attribute to the pivot data array.

```php
class Category extends Model
{
    use DamianLewis\SortableRelations\Traits\SortableRelations;

    public $table = 'acme_plugin_categories';

    public $belongsToMany = [
        'products' => [
            'Acme\Plugin\Models\Product',
            'table' => 'acme_plugin_category_product',
            'pivot' => ['relation_sort_order']
        ]
    ];

    public $sortableRelations = [
        'products'
    ];
}
```

Implement the `DamianLewis\SortableRelations\Behaviors\SortableRelations` behavior within the parent controller of the relation and define the `$sortableRelationConfig` configuration file. This file should define the model class and the name of the sortable relation.

```php
class Categories extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
        'DamianLewis.SortableRelations.Behaviors.SortableRelations'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';
    public $sortableRelationConfig = 'config_sortable_relation.yaml';

    ...
}
```

Example sortable relations configuration file `config_sortable_relation.yaml`:
```yaml
modelClass: Acme\Plugin\Models\Category
relationName: products
```

Make sure the `pivot[sort_order]` column has been included in the view.list configuration for the relation controller.

Example relations configuration file `config_relation.yaml`:
```yaml
products:
    label: Product
    view:
        list:
            columns:
                title:
                    label: Title
                    type: partial
                pivot[relation_sort_order]:
                    label: Order
                    type: number
                    invisible: true
    manage:
        list: $/acme/plugin/models/product/columns.yaml
        form: $/acme/plugin/models/product/fields.yaml
```

Lastly, two hidden input fields need to be added to the table rows. One for the parent model ID and the other for the related model ID. This can be accomplished by using a column partial type. The input field for the parent model should include a `Lists-relationViewList-parent-id` id attribute with a value equal to the parent model ID. The input field for the related model should include a `Lists-relationViewList-related-id` id attribute with a value equal to the related model ID.

Example partial file for the `title` column:
```html
<input type="hidden" id="<?= 'Lists-relationViewList-parent-id-'.$record->pivot->category_id ?>" value="<?= $record->pivot->category_id ?>">
<input type="hidden" id="<?= 'Lists-relationViewList-related-id-'.$record->pivot->product_id ?>" value="<?= $record->pivot->product_id ?>">
<?= $value ?>
```

## Note
If extending the relation configuration for the parent controller, make sure to include a call to the 'SortableRelations' `relationExtendConfig` method as shown in the following example.
```php
class Categories extends Controller
{
    ...
    
    public function relationExtendConfig($config, $field, $model)
    {
        $this->asExtension('SortableRelations')->relationExtendConfig($config, $field, $model);
    }
    
    ...
}

```