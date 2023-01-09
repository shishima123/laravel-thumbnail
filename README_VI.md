# Laravel Document Thumbnail
Package này được sử dụng để tạo ảnh thumbnail cho file document hoặc file image. Nó hỗ trợ 1 số định dạng file sau: doc, docx, xls, xlsx, gif, jpg, jpeg, png.
## Installation
Thêm cài đặt link github vào `"repositories"` trong file root  *composer.json*, thêm vào phía sau `"prefer-stable": true`

    "prefer-stable": true,
    "repositories": [
            {
                "type": "vsc",
                "url": "https://github.com/shishima123/laravel-thumbnail"
            }
        ]

Sau khi thêm package thì cài đặt bằng câu lệnh:

    composer require phuocnguyen/laravel-thumbnail

### Config
Để thay đổi các config mặc định thì sử dụng câu lệnh bên dưới 

### Publish config

    php artisan vendor:publish --provider=" PhuocNguyen\Thumbnail\ThumbnailServiceProvider" --tag="thumbnail-config"

Sau khi đã publish thì có thể vào file app/config/thumbnail để chỉnh sửa.

### Note
Phần config disk cần phải có 2 config, 

Config `disks.temp_thumbnail` được dùng với mục đích là trong quá trình tạo thumnail 
thì package sẽ phải clone file gốc ra thư mục temp, và thực hiện một số chỉnh sửa trên đó. 
Sau khi chỉnh sửa xong và xuất ra file thumbnail thì file tạm này sẽ bị xóa.

Config `disk.thumbnail` được dùng để lưu file thumbnail được tạo ra.

Có thể chỉnh sửa lại các config nếu cần thiết.

Config `default` được dùng trong trường hợp là nếu file không nằm trong danh sách loại file 
hỗ trợ tạo thumbnail thì có thể dùng icon mặc định thay thế. Có thể tắt đi bằng cách truyền giá trị enable = false.

Mặc định thì khi cài đặt package, 1 file default sẽ được publish vào đường dẫn `/vendor/laravel_thumbnail/Thumbnail-default.svg`
Đường dẫn này dùng để chỉ định file default. Có thể thay đổi thành đuờng dẫn khác nếu cần thiết.

Các config khác thì có thể đọc thêm mô tả của từng config
## Sử dụng
### Tạo file thumbnail
Để sử dụng thì có thể sử dụng Thumbnail Facade:
```php
use PhuocNguyen\Thumbnail\Facade\Thumbnail;

Thumbnail::setFile($file)->create();
```
File ở đây có thể là đường dẫn tới file nằm trên hệ thống, ví dụ: `var/www/public/files/example.docx`.

Hoặc sẽ là file được lấy ra từ `request` lúc dùng phương thức post ở form submit với `<input type=file>`.
```php
use PhuocNguyen\Thumbnail\Facade\Thumbnail;

Thumbnail::setFile($request->file('file'))->create();
```

Method `setFile` được dùng để truyền vào file sẽ được tạo thumbnail. Hoặc có thể sử dụng bằng cách truyền thông qua method `create`
```php
Thumbnail::create($file);
```

### Thay đổi các options
Để thay đổi height mặc định có thể dùng method `setHeight`
```php
Thumbnail::setHeight(100)->create($file);
```

Để thay đổi width mặc định có thể dùng method `setWidth`
```php
Thumbnail::setWidth(100)->create($file);
```

Để thay đổi format mặc định có thể dùng method `setFormat`
```php
Thumbnail::setFormat('png')->create($file);
```

Nếu ảnh xuất ra bị lỗi hiển thị, thử thay đổi tham số layer bằng method `setLayer`
```php
Thumbnail::setLayer(20)->create($file);
```

Có thể thay đổi cùng lúc nhiều tham số options bằng method `setOptions`, tham số truyền vào sẽ được truyền dưới dạng array

Package chỉ hỗ trợ thay đổi: width, height, layer, format.
```php
$options = [
        'width' => 200,
        'height' => 200,
        'format' => 'png',
        'layer' => 20
    ];
Thumbnail::setOptions($options)->create($file);
```

Để bỏ qua các định dạng file hỗ trợ thì sử dụng method `setIgnore`
```php
Thumbnail::setIgnore(['png', 'gif'])->create($file);
```

## Hỗ trợ model events

