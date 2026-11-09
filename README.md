# C21 Repeater for ACF Free

A standalone WordPress plugin adding a **C21 Repeater (Free)** custom ACF field type. It **does not require ACF PRO**. ACF Free must be installed and active.

## Install

1. Upload `c21-acf-free-repeater.zip` through WordPress Plugins → Add New → Upload Plugin.
2. Activate it alongside ACF Free.
3. Go to ACF → Field Groups → edit/create a field group for `rm_menu_item`.
4. Add a field named `variations` with field type **C21 Repeater (Free)**.
5. Enter the subfields in the field settings (one per line):

```
var_desc_ar|Arabic Description|text
var_desc_en|English Description|text
var_price|Price|number
```

Supported types: `text`, `textarea`, `number`, `select`. Select example: `size|Size|select|sm:Small,md:Medium,lg:Large`.

6. Set **Maximum rows = 0** for unlimited rows. Save the field group.
7. Edit a menu item, add, remove or drag rows, and save.

## Read rows in your C21 theme

```php
$variations = get_field('variations', $post_id);
if (is_array($variations)) {
    foreach ($variations as $variation) {
        echo esc_html($variation['var_desc_en'] ?? '');
        echo esc_html($variation['var_price'] ?? '');
    }
}
```

## Import rows from your existing importer

Once you have grouped the CSV rows into an array of variations, pass the whole array to `update_field()`. Use the **actual ACF field key** (e.g. `field_...`) for initial creation; the field name may not work before ACF's reference metadata exists.

```php
$rows = [
    ['var_desc_ar' => 'صغير', 'var_desc_en' => 'Small', 'var_price' => '60'],
    ['var_desc_ar' => 'كبير', 'var_desc_en' => 'Large', 'var_price' => '100'],
];
update_field('field_YOUR_C21_REPEATER_FIELD_KEY', $rows, $post_id);
update_field('has_variation', 1, $post_id);
```

## Export rows

```php
$rows = get_field('variations', $post_id); // returns array of associative rows
foreach ((array) $rows as $index => $row) {
    // Map row to your current CSV/Excel format.
}
```

## How this differs from ACF PRO Repeater

- The field is registered through ACF Free's public custom-field-type API.
- The rows are saved **as a single serialized array in wp_postmeta**, not as ACF PRO Repeater's individually indexed subfield metadata. ACF `get_field`/`update_field` works, but PRO's `have_rows()`, `the_row()`, `get_sub_field()`, and `add_row()` helpers are **not implemented**.
- Subfields are configured with one-line definitions (name|label|type), not ACF PRO's nested graphical field editor.
- No nested repeaters, conditional subfields, or complex ACF field types. Avoid swapping an existing PRO Repeater field to this field type without migrating its data.
- Maximum rows 0 means no configured limit, but PHP memory, web-server limits and `max_input_vars` still apply to very large forms.
- The plugin focuses on field UI and values; it does not replace your existing importer/exporter. Adapt those modules to use the array returned from `get_field()`.

## Notes

- Back up the WordPress database before deploying.
- Test in staging first. Not validated with your live C21 installation.
- Subfield names should not be renamed after data is created without a migration.
- # For truly huge variation counts, use a separate database structure or dedicated CPT rather than a very large admin form.
