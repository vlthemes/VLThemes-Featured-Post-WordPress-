# VLThemes Featured Post (WordPress)
This class allows you to add featured posts to your blog

You can use this construction in WP_Query:
```php
 $new_query = new WP_Query(array(
    'post_type' => 'post',
    'meta_query' => array(
         array(
             'key' => '_is_featured',
             'value' => 'yes',
         ),
    )
));
```

Also you can check if this post featured:
```php
if ( get_post_meta( get_the_ID(), '_is_featured', true ) === 'yes' ) {
// Do action
}
```
