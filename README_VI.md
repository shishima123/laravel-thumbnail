# Laravel Document Thumbnail
Package này được sử dụng để tạo ảnh thumbnail cho file document hoặc file image. Nó hỗ trợ 1 số định dạng file sau: doc, docx, xls, xlsx, gif, jpg, jpeg, png.

## Requirements
Package này chỉ hoạt động trên môi trường linux. Để sử dụng thì bắt buộc môi trường linux phải có cài đặt sẵn các lib sau:

#### libmagickwand

    sudo apt-get install libmagickwand-dev --no-install-recommends

#### ghostscript

    sudo apt-get install ghostscript

#### libreoffice

    sudo apt-get install libreoffice

#### unoconv

    sudo apt-get install unoconv

Các câu lệnh cài đặt chỉ dùng để tham khảo, tuỳ thuộc vào distro tương ứng để sử dụng câu lệnh cài đặt chính xác hơn.

#### Imagick
Package sử dụng extension imagick của php để tạo file thumbnail nên bắt buộc cài đặt extension này.

    sudo apt install php-imagick

    //then
    
    sudo apt list php-magick -a

Sau khi cài đặt nhớ restart lại server để kích hoạt extension.

Copy file `policy.xml` vào đường dẫn `/etc/ImageMagick-6/policy.xml`. <br>
File này dùng để cấp quyền đọc ghi file pdf cho imagick.

Copy file `Module1.xba` vào đường dẫn `/usr/lib/libreoffice/presets/basic/Standard/Module1.xba`.<br>
File này dùng để sửa lỗi trang bị xuống dòng khi tạo thumbnail cho file excel.

### Docker
Nếu sử dụng docker thì có thể thêm các dòng sau đây vào docker file để cài đặt các lib cần thiết cho linux.

    # Install dependencies
    RUN apt-get update && apt-get install -y \
        libmagickwand-dev --no-install-recommends \
        ghostscript \
        libreoffice \
        unoconv

    # Install php extensions
    RUN pecl install imagick
    RUN docker-php-ext-enable imagick

Copy 2 file `policy.xml` và `Module1.xba` vào vào chung với file docker-compose.yml.

Xong thêm vào phần volumes của app.

    laravel_app:
        // ***
        volumes:
          // ***
          - ./policy.xml:/etc/ImageMagick-6/policy.xml
          - ./Module1.xba:/usr/lib/libreoffice/presets/basic/Standard/Module1.xba

Có thể tham khảo thư mục docker để biết thêm cách setup.

## Installation
Cài đặt bằng composer:

    composer require phuocnguyen/laravel-thumbnail

### Publish config

    php artisan vendor:publish --provider="PhuocNguyen\Thumbnail\ThumbnailServiceProvider" --tag="thumbnail-config"

Sau khi đã publish config thì có thể vào file `app/config/thumbnail` để chỉnh sửa.

### Notes
#### Disks
Phần config `disks` cần phải có 2 config,

-   __disks.temp_thumbnail__  được dùng với mục đích là trong quá trình tạo thumnail
    thì package sẽ phải clone file gốc ra thư mục temp, và thực hiện một số chỉnh sửa trên đó.
    Sau khi chỉnh sửa xong và xuất ra file thumbnail thì file tạm này sẽ bị xóa.

-   __disk.thumbnail__ được dùng để lưu file thumbnail được tạo ra.

Mặc định thì file được lưu trong storage, nếu dùng mặc định thì phải symlink qua public.

    php artisan storage:link

Có thể chỉnh sửa lại các config nếu cần thiết.

#### Default
Config `default` được dùng trong trường hợp là nếu file không nằm trong danh sách loại file
hỗ trợ tạo thumbnail thì có thể dùng icon mặc định thay thế. Có thể tắt đi bằng cách truyền giá trị enable = false.

Mặc định thì khi cài đặt package, 1 file default sẽ được publish vào đường dẫn `public/vendor/laravel_thumbnail/Thumbnail-default.svg`
Đường dẫn này dùng để chỉ định file default. Có thể thay đổi thành đuờng dẫn khác nếu cần thiết.

### Ignore extensions

Mặc định thì package hỗ trợ các extension sau: doc, docx, xls, xlsx, gif, jpg, jpeg, png. Nếu muốn bỏ qua extension nào thì thêm vào `ignore_extensions`
```php
'ignore_extensions' => ['png', 'jpg']
```
## Usage
### Tạo file thumbnail
Để sử dụng thì có thể sử dụng Thumbnail Facade:
```php
use PhuocNguyen\Thumbnail\Facade\Thumbnail;

Thumbnail::setFile($file)->create();
```
File ở đây có thể là đường dẫn tới file nằm trên hệ thống.
```php
use PhuocNguyen\Thumbnail\Facade\Thumbnail;

$file = public_path('files/example.docx');
Thumbnail::setFile($file)->create();
```

Hoặc sẽ là file được lấy ra từ `request` lúc dùng phương thức post ở form submit với `<input type=file>`.

```php
use PhuocNguyen\Thumbnail\Facade\Thumbnail;

Thumbnail::setFile($request->file('file'))->create();
```

Method `setFile` được dùng để truyền vào file sẽ được tạo thumbnail, hoặc có thể sử dụng bằng cách truyền param vào method `create`.
```php
Thumbnail::create($file);
```

Data trả về của hàm `create` sẽ có format như sau:
```php
[
    'name' => 'thumbnail_name',
    'origin_name' => 'thumbnail_origin_name'
    'path' => 'path to file'
]
```

### Thay đổi các options
Các options có thể được cấu hình mặc định trong file `config/thumbnail`. Nhưng vẫn có thể thay đổi trong quá trình tạo file bằng cách sử dụng một số methods sau:
#### setHeight
Thay đổi height mặc định:
```php
Thumbnail::setHeight(100)->create($file);
```

#### setWidth
Thay đổi width mặc định:
```php
Thumbnail::setWidth(100)->create($file);
```

#### setSize
Thay đổi width và height mặc định:
```php
Thumbnail::setSize(width: 200, height: 200)->create($file);
```

#### setFormat
Thay đổi format mặc định:
```php
Thumbnail::setFormat('png')->create($file);
```

#### setLayer
Nếu ảnh xuất ra bị lỗi hiển thị, thử thay đổi tham số layer:
```php
Thumbnail::setLayer(20)->create($file);
```

#### setOptions
Để thay đổi cùng lúc nhiều tham số options có thể sử dụng method `setOptions`, tham số truyền vào sẽ được truyền dưới dạng array

__IMPORTANT!__ Package chỉ hỗ trợ thay đổi: width, height, layer, format:
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
Tính năng này hoạt động dựa trên cơ chế model events của laravel, khi request có file upload, và được lưu vào database thì sẽ thực hiện việc tạo thumbnail tự động.

### Migration
Package đã có sẵn file migration dùng để tạo table `thumbnails` dùng để lưu record của thumbnail vừa tạo.

Migrate table `thumbnails`:

    php artisan migrate

### Publish migration
Mặc định chỉ sử dụng các column mặc định trong file migration, nếu muốn tuỳ chỉnh thêm column thì có thể publish file migration rồi chỉnh theo ý muốn.

Với các column mới được thêm thì có thể tham khảo [Custom Data Save](#custom-data-save) để thêm dữ liệu.

Để publish:

    php artisan vendor:publish --provider="PhuocNguyen\Thumbnail\ThumbnailServiceProvider" --tag="thumbnail-migrations"

Sau khi publish vào file `app/Providers/AppServiceProvider.php` thêm dòng lệnh bên dưới để bỏ qua file migration mặc định.

```php
use PhuocNguyen\Thumbnail\HasThumbnail;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
        Thumbnail::ignoreMigrations();
    }
}
``` 

Sau khi đã chỉnh sửa file `migration` thì dùng lệnh `php artisan migrate` để tạo table mới.

### Thay đổi tên table
Nếu muốn thay đổi tên table thì có thể thay đổi `table_name` trong file `config/thumbnail`.

### Thay đổi model
Mặc định package sẽ dùng model `\PhuocNguyen\Thumbnail\Models\Thumbnail::class` để lưu dữ liệu vào database.

Có thể thay đổi bằng cách thay đổi `thumbnail_model` trong file `config/thumbnail`.

### Usage
Để sử dụng chức năng tự động tạo thumbnail thì thêm trait `HasThumbnail` ở class Model:

Ví dụ:

```php
use PhuocNguyen\Thumbnail\HasThumbnail;

class Document extends Models
{
    use HasThumbnail;
    //
}
``` 

Sau khi thêm `HasThumbnail` thì khi nào request hiện tại có chứa file gửi lên từ client, <br>
và đang thực hiện việc lưu dữ liệu thông qua Model Document thì sẽ kích hoạt việc tạo thumbnail tự động.

### Tuỳ chỉnh events
Package chỉ hỗ trợ 2 events sau đây: `saved` và `updated`. Nếu muốn tuỳ chỉnh events thì có thể đặt thuộc tính `$thumbnailEvents` trong file model:

```php
protected static $thumbnailEvents = ['saved'];
``` 

__IMPORTANT!__ package chỉ hỗ trợ 2 events là `saved` và `updated`. Vì vậy, ngoài 2 events trên thì các event khác có được thêm vào cũng không hỗ trợ.

### Disable
Để vô hiệu hoá chức năng tự tạo thumbnail thì sử dụng propery `$doNotCreateThumbnail`.
```php
protected static $doNotCreateThumbnail = true;
``` 

### Options
Để tùy chỉnh các options ở Model thì sử dụng property `$thumbnailOptions`.
```php
protected static array $thumbnailOptions = [
    'height' => 200,
    'width' => 200,
    'format' => 'png',
    'layer' => 12
];
``` 

### Custom Data Save
Để tuỳ chỉnh data trước khi save có thể dùng `$thumbnailSaveData`.
```php
protected static function thumbnailSaveData($thumbnail, $file, $model): array
{
    return [
        'name' => 'custom_name',
    ];
}
```

Các tham số truyền vào là:

-   __$thumbnail__ Data trả về của Facade Thumbnail
-   __$file__ File được upload lên từ form submit
-   __$model__ Model hiện tại đang thực hiện việc save dữ liệu

Ở phần trên có đề cập là có thể tuỳ chỉnh được file migration, ví dụ trong file migration thêm vào 1 column mới là `mime`.

Mặc định thì package sẽ không hỗ trợ lưu data cho những cột tuỳ chỉnh như này.

Vì vậy, chúng ta có thể dùng method `thumbnailSaveData` để tuỳ chỉnh data trước khi được đưa vào Model Thumbnail xử lí

Ví dụ:
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
Nếu không muốn sử dụng migration và model mặc định của package để lưu record thumbnail khi model events được kích hoạt, <br>
thì có thể sử dụng method `thumbnailCustomSave` để tuỳ chỉnh.

Ví dụ: Trong model hiện tại có 1 column thumbnail để lưu path thì có thể viết như sau:

```php
protected static function thumbnailCustomSave($thumbnail, $file, $model)
    {
        $model->thumbnail = $thumbnail['path'];
        $model->saveQuietly();
        
        // Or
        // CustomModelThumbnail::insert($data);
    }
```

__IMPORTANT!__ Sử dụng các method không kích hoạt model events như `saveQuietly` hoặc `insert` để tránh bị lỗi loop vô tận.

### Overwrite On Update
Mặc định thì mỗi lần model event được kích hoạt thì các record trong table `thumbnails` sẽ thêm mới.<br>
Nếu muốn khi update data cho model hiện tại sẽ cập nhật lại các record cũ thì sử dụng `$thumbnailUpdateWillOverwrite`.
```php
protected static bool $thumbnailUpdateWillOverwrite = true;
```
