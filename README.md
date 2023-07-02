# Laravel Document Thumbnail

This package is used to create thumbnail images for document or image files. It supports the following file formats:
doc, docx, xls, xlsx, pdf, gif, jpg, jpeg, png.

## Requirements

This package only works on a Linux environment. In order to use it, the following libraries must be pre-installed on the
Linux environment:

#### libmagickwand

    sudo apt-get install libmagickwand-dev --no-install-recommends

#### ghostscript

    sudo apt-get install ghostscript

#### libreoffice

    sudo apt-get install libreoffice

#### unoconv

    sudo apt-get install unoconv

Those installation commands are for reference only, and the specific commands to use depend on the corresponding
distribution. Please use the appropriate commands for your distribution to ensure accurate installation.

#### Imagick

This package uses the Imagick extension of PHP to create thumbnail files, so it is necessary to install this extension.

    sudo apt install php-imagick

    //then
    
    sudo apt list php-magick -a

After installation, remember to restart the server to activate the extension.

Copy the `policy.xml` file to the path `/etc/ImageMagick-6/policy.xml`.
This file is used to grant Imagick read and write permissions for PDF files.

Copy the `Module1.xba` file to the path `/usr/lib/libreoffice/presets/basic/Standard/Module1.xba`.
This file is used to fix the line break issue when creating thumbnails for Excel files.

### Docker

If you are using Docker, you can add the following lines to your Dockerfile to install the necessary libraries for
Linux:

    # Install dependencies
    RUN apt-get update && apt-get install -y \
        libmagickwand-dev --no-install-recommends \
        ghostscript \
        libreoffice \
        unoconv

    # Install php extensions
    RUN pecl install imagick
    RUN docker-php-ext-enable imagick

Copy the `policy.xml` and `Module1.xba` files into the same directory as the `docker-compose.yml` file.

Then, add them to the `volumes` section of the app.

    laravel_app:
        // ***
        volumes:
          // ***
          - ./policy.xml:/etc/ImageMagick-6/policy.xml
          - ./Module1.xba:/usr/lib/libreoffice/presets/basic/Standard/Module1.xba

For more information, please refer to the Docker directory.

## Installation

Install by using composer:

    composer require shishima/laravel-thumbnail

### Publish config

    php artisan vendor:publish --provider="Shishima\Thumbnail\ThumbnailServiceProvider" --tag="thumbnail-config"

After publishing the configuration file, you can edit the app/config/thumbnail file to customize the settings.

#### Publish default thumbnail icon

    php artisan vendor:publish --provider="Shishima\Thumbnail\ThumbnailServiceProvider" --tag="thumbnail-assets"

A default file is published to the path `public/vendor/laravel_thumbnail/Thumbnail-default.svg`. This path is used to
specify the default file. You can change it to a different path if necessary.

The `default` config is used in case the file is not in the list of supported thumbnail file types. In this case, a
default icon can be used as a replacement. You can disable this feature by setting `enable = false`.

### Notes

#### Disks

The configuration section `disks` includes two configurations:

- The __disks.temp_thumbnail__ configuration is utilized to temporarily clone the original file to the temp directory during the thumbnail creation process. <br>This allows for modifications to be made to the file before generating the thumbnail. Once the thumbnail is generated, the temporary file is deleted.

- The __disk.thumbnail__ configuration is used to store the generated thumbnail files.

By default, these files are stored in the storage directory. If you opt to use the default configuration, you will need to create a symbolic link to the public directory.

    php artisan storage:link

You can customize these settings as necessary.

### Ignore extensions

By default, the package supports the following file extensions: doc, docx, xls, xlsx, pdf, gif, jpg, jpeg, png. However, if you want to exclude a specific extension from being processed, you can add it to the `ignore_extensions` list.

```php
'ignore_extensions' => ['png', 'jpg']
```

## Usage

### Create thumbnail file

To use the package, you can use the `Thumbnail` facade:

```php
use Shishima\Thumbnail\Facade\Thumbnail;

Thumbnail::setFile($file)->create();
```

In this example, the file can be a path to a file on the system:

```php
use Shishima\Thumbnail\Facade\Thumbnail;

$file = public_path('files/example.docx');
Thumbnail::setFile($file)->create();
```

Or it could be a file retrieved from the `request` when using the `POST` method in a form submit
with `<input type="file">`.

```php
use Shishima\Thumbnail\Facade\Thumbnail;

Thumbnail::setFile($request->file('file'))->create();
```

The `setFile` method is used to pass in the file that will have its thumbnail generated, or it can be done by passing
the file as a parameter to the `create` method.

```php
Thumbnail::create($file);
```

The data returned by the `create` function will have the following format:

```php
[
    'name' => 'thumbnail_name',
    'origin_name' => 'thumbnail_origin_name'
    'path' => 'path to file'
]
```

### Changing options

The default options can be configured in the `app/config/thumbnail` file, but they can still be changed during the
thumbnail generation process by using the following methods:

#### setHeight

Changes the default height:

```php
Thumbnail::setHeight(100)->create($file);
```

#### setWidth

Changes the default width:

```php
Thumbnail::setWidth(100)->create($file);
```

#### setSize

Changes both the default width and height:

```php
Thumbnail::setSize(width: 200, height: 200)->create($file);
```

#### setFormat

Changes the default format of the thumbnail:

```php
Thumbnail::setFormat('png')->create($file);
```

#### setLayer

If the generated thumbnail image is not displayed correctly, you can try changing the layer parameter:

```php
Thumbnail::setLayer(20)->create($file);
```

#### setOptions

To change multiple options at once, you can use the `setOptions` method and pass in an array of options:

__IMPORTANT!__ The package only supports changing: width, height, layer, and format options.

```php
$options = [
        'width' => 200,
        'height' => 200,
        'format' => 'png',
        'layer' => 20
    ];
Thumbnail::setOptions($options)->create($file);
```

## Model events

This feature relies on the model events feature of Laravel. When a request with a file upload is saved to the database,
the thumbnail will be automatically created.

### Publish migration

This package comes with a migration file that is used to create the thumbnails table which is used to store records of
the recently created thumbnail.

To publish:

    php artisan vendor:publish --provider="Shishima\Thumbnail\ThumbnailServiceProvider" --tag="thumbnail-migrations"

Migrate table after publish:

    php artisan migrate

#### Customize migration file

To customize the columns in the thumbnails table, you can change the generated migration file.

After modifying the migration file, use the `php artisan migrate` command to create a new table.

To add new columns, you can refer to [Custom Data Save](#custom-data-save) to add the data.

### Change table name

To change the table name, you can change `table_name` in the `app/config/thumbnail` file.

### Change model

By default, the package will use the `\Shishima\Thumbnail\Models\Thumbnail::class` model to save data to the database.

You can change this by changing `thumbnail_model` in the `app/config/thumbnail` file.

### Usage

To use the automatic thumbnail creation feature, add the `HasThumbnail` trait to the Model class:

For example:

```php
use Shishima\Thumbnail\HasThumbnail;

class Document extends Models
{
    use HasThumbnail;
    //
}
``` 

### Thumbnail Event Trigger Configuration
After adding HasThumbnail, you will need to specify the $thumbnailEventTriggerColumn. This column will store the path of the file for which you want to generate a thumbnail.

```php
protected static string $thumbnailEventTriggerColumn = 'file_path';
``` 

### Disk Configuration
By default, the package will check files using Laravel's Storage class.

If you save files using Laravel's Storage and your disk is different from the `filesystems.default` configured in `config/filesystems`, you need to configure the disk information by:

```php
protected static function getDiskOfFileUploaded(): string
{
    return 'local_public';
}
```
__IMPORTANT!__ The current version of the package only supports disks stored locally. Cloud storage will be supported in the future.

### Custom Events

The package offers support for two events: `saved` and `updated`. If you wish to customize these events, you can achieve that by setting the `$thumbnailEvents` attribute in the model file.

```php
protected static $thumbnailEvents = ['saved'];
``` 

__IMPORTANT!__ The package only supports 2 events: `saved` and `updated`. Therefore, other events cannot be added and
are not supported."

### Disable

To disable the thumbnail creation feature, use the `$doNotCreateThumbnail` property:

```php
protected static $doNotCreateThumbnail = true;
``` 

### Options

To customize the options in the Model, use the `$thumbnailOptions` property:

```php
protected static array $thumbnailOptions = [
    'height' => 200,
    'width' => 200,
    'format' => 'png',
    'layer' => 12
];
``` 

### Custom Data Save

To customize the data before saving, you can use `$thumbnailSaveData`:

```php
protected static function thumbnailSaveData($thumbnail, $file, $model): array
{
    return [
        'name' => 'custom_name',
    ];
}
```

The parameters passed are:

- __$thumbnail__ Data returned by the Thumbnail Facade
- __$file__ File uploaded from the form submission
- __$model__ Current Model performing the data save

As mentioned earlier, you have the ability to customize the file migration by adding a new mime column, for instance.

By default, the package does not provide support for saving data in custom columns like this.

To address this, you can utilize the `thumbnailSaveData` method, which allows you to customize the data before it undergoes processing by the Thumbnail Model.

For example:

```php
protected static function thumbnailSaveData($thumbnail, $file, $model): array
{
    $mime = $file->getMimeType();
    return [
        'name' => 'custom_name',
        'mime' => $mime
    ];
}
```

### Custom Handle Save Data

If you prefer not to utilize the default migration and model included in the package for saving thumbnail records when model events are triggered, you have the option to customize this functionality using the `thumbnailCustomSave` method.

To illustrate, let's consider a scenario where the current model has a thumbnail column for storing the file path. In this case, you can implement the following code:

```php
protected static function thumbnailCustomSave($thumbnail, $file, $model)
    {
        $model->thumbnail = $thumbnail['path'];
        $model->saveQuietly();
        
        // Or
        // CustomModelThumbnail::insert($data);
    }
```

__IMPORTANT!__ Use methods that do not trigger model events, such as `saveQuietly` or `insert`, to avoid infinite loop
errors.

### Overwrite On Update

By default, whenever a model event is triggered, new records are added to the `thumbnails` table.

However, if you wish to update the existing records when modifying data for the current model, you can make use of the `$thumbnailUpdateWillOverwrite` feature.

```php
protected static bool $thumbnailUpdateWillOverwrite = true;
```

## Testing

Run the tests with:

    composer test
