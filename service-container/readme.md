# Service Container

## Apa itu Service Container ?
-------------------------

Seperti yang ditulis pada website dokumentasi Laravel, disebutkan bahwa Service Container adalah tools yang digunakan untuk mengatur dependency dari class dan juga penggunaan class itu sendiri.

Maksudnya? Seperti yang kita ketahui, umumn	ya sebuah class mempunyai property dan method. Ketika kita mau memanggil method dari class tersebut di class lain, maka yang biasa kita lakukan adalah menginisialisasikannya untuk kemudian dijadikan object. Baru setelah itu kita panggil methodnya.

```
$car = new Car();
return $car->price();

```

Nah, tugas dari Service Container disini adalah mengatur dari pemanggilan class, komunikasi antar satu class dengan class lain, inisialisasi sebuah class menjadi object, agar menjadi lebih mudah dan powerful.

## Kenapa harus Service Container?
-------------------------

Jadi, kenapa sih sebenarnya kita harus repot-repot pakai Service Container, bukannya udah enak pakai cara konvensional yang seperti di contoh sebelumnya atau kan juga udah ada "injection lewat parameter" dari Laravel (eh, yang ini udah pada tau belum? hehe)

Oke, mari kita bahas. Anggap saja kita ingin membuat sebuah layanan pemesanan barang, tentu kita membutuhkan sebuah controller untuk menangani itu, sebut saja nama controller-nya PayOrderController

Di dalam controller ini kita akan buat method yang akan mengeluarkan output response dari detail biaya yang harus dikeluarkan. Terlihat dalam controller tersebut kita menginisalisasi class PaymentGateway

Anggap saja, PaymentGateway adalah class yang nantinya akan dipakai di berbagai class bukan hanya di PayOrderController, seperti misal untuk OrderDetails, TopUp, dll.

**PaymentGateway.php**

```php
<?php

namespace App\Billing;

use Illuminate\Support\St;

class PaymentGateway {

    private $currency;
    
    public function charge($amount) {
        // Charge the bank
        return [
            'amount' => $amount - $this->discount,
            'confirmation_number' => Str::random(),
        ];
    }
}
```

**PayOrderController.php**

```php
<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Orders\OrderDetails;
use Illuminate\Http\Request;

class PayOrderController extends Controller
{
    public function store() {
        $paymentGateway = new PaymentGateway();
        dd($paymentGateway->charge(2500));
    }
}
```

Di atas ini adalah cara umum ketika kita memanggil dan menggunakan sebuah class, disini kita panggil method charge dan mengembalikan value berupa array. sNah, seperti yang saya sebutkan sebelumnya laravel punya fitur untuk menginjeksi sebuah class dan merefleksikannya dalam bentuk parameter, sehingga kita tidak perlu untuk menginisialisasi class tersebut.

```php
<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Orders\OrderDetails;
use Illuminate\Http\Request;
class PayOrderController extends Controller
{
    public function store(PaymentGateway $paymentGateway) {
        dd($paymentGateway->charge(2500));
    }
}
```

Dengan begini, kode kita akan jauh lebih ringkas dan sebenarnya ada juga kelebihan kelebihan lain yang bisa didapatkan jika menggunakan cara ini, bersama kita akan lihat nanti..

> Tapi, bagaimana kalau class tersebut memiliki constructor yang mengharuskan untuk menginputkan argumen?

Oke, sebagai contoh kita akan modifikasi class PaymentGateway menjadi seperti berikut

```php
<?php

namespace App\Billing;

use Illuminate\Support\Str;

class PaymentGateway {

    private $currency;
    
    public function __construct($currency)
    {
        $this->currency = $currency;
}




    
public function charge($amount) {
        // Charge the bank
        return [
            'amount' => $amount - $this->discount,
            'confirmation_number' => Str::random(),
            'currency' => $this->currency,
        ];
    }
}
```

Sekarang, class PaymentGateway memerlukan inputan parameter currency

`$paymentGateway = new PaymentGateway('idr');`

Jika kita menggunakan cara seperti itu untuk memanggil class tersebut, maka tak ada masalah. Namun, bagaimana caranya menerapkan-nya agar class dapat dipanggil tanpa melakukan inisalisasi seperti diatas?

> Jawabannya, tidak bisa !

Pada dasarnya, untuk menggunakan sebuah class memang sebelumnya kita harus melakukan inisialisasi terlebih dahulu, cara menginjeksi sebuah class dan merefleksikannya dalam bentuk parameter sebetulnya juga class tersebut diinisalisasikan dulu sebelumnya. Namun, bukan oleh kita, melainkan dari laravel itu sendiri. Oleh karena itu, disinilah **Service Container** berperan, dia dapat menyimpan inisialisasi-inisialisasi dari class - class yang ada, sehingga ketika kita ingin menggunakannya kita tidak perlu menginisalisasikannya kembali.

Untuk menggunakan Service Container, kita dapat mensetup-nya melalu **app\providers\AppServiceProvider.php**

```php

<?php

namespace App\Providers;

use App\Billing\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */


    public function register()
    {
        $this->app->bind(PaymentGateway::class, function($app) {
            return new PaymentGateway('idr');
        });
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

```
Di dalam AppServiceProvider terdapat dua method default yaitu `register()` dan `boot()`, register method digunakan untuk menginjeksi sebuah class sedangkan boot method digunakan untuk merender view. Sehingga, untuk studi kasus kali ini kita akan menggunakan register method.


```php
        $this->app->bind(PaymentGateway::class, function($app) {
            return new PaymentGateway('idr');
        });
```

Barisan kode di atas, kalau diartikan, ketika ada class manapun yang membutuhkan atau memanggil PaymentGateway maka kembalikan inisalisasi-nya. Jadi, kita dapat memanggilnya hanya dengan seperti ini

```php

public function store(PaymentGateway $paymentGateway) {
        dd($paymentGateway->charge(2500));
}

```
Next, bagaimana jika ada class lain yang juga menggunakan PaymentGateway, misal kita akan buat class OrderDetails, seperti berikut

```php

<?php

namespace App\Orders;

use App\Billing\PaymentGateway;


class OrderDetails {

    private $paymentGateway;
    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway; 
}

    public function all() {
        $this->paymentGateway->setDiscount(500);
        return [
            'name' => 'Linux Hirarki',
            'address' => 'Semarang, Indonesia'
        ];
    }
}

```

Lalu, kita tambahkan method baru `setDiscount()` di **PaymentGateway.php**

```php
<?php

namespace App\Billing;

use Illuminate\Support\Str;

class PaymentGateway {

    private $currency;
    private $discount;
    
    public function __construct($currency)
    {
        $this->currency = $currency;
        $this->discount = 0;
    }
    
    public function setDiscount($amount) {
        $this->discount = $amount;  
    }


    public function charge($amount) {
        // Charge the bank
        return [
            'amount' => $amount - $this->discount,
            'confirmation_number' => Str::random(),
            'currency' => $this->currency,
            'discount' => $this->discount
        ];
    }
}

```
Baru saja kita menggunakan class PaymentGateway di OrderDetails tanpa menginisalisasikannya, sekarang kita panggil kedua class tersebut di **PayOrderController**

```php

<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Orders\OrderDetails;
use Illuminate\Http\Request;

class PayOrderController extends Controller
{
public function store(OrderDetails $orderDetails, PaymentGateway $paymentGateway) {

        $order = $orderDetails->all();
        dd($paymentGateway->charge(2500));
    }
}

```

Jika kita jalankan, maka yang akan muncul adalah sebagai berikut

```

[
  "amount" => 2500
  "confirmation_number" => "pQelPgQQMQGQZ9dq"
  "currency" => "usd"
  "discount" => 0
]

```

Kalau kita perhatikan discount-nya bernilai 0, padahal sebelumnya kita menset nilainya senilai 500, mengapa hal ini bisa terjadi?

Hal ini dapat terjadi dikarenakan, class PaymentGateway yang kita panggil di PayOrderController berbeda dengan yang kita panggil di OrderDetails, itu adalah object yang berbeda, sehingga ketika setiap PaymentGateway dipanggil dia akan mengcreate PaymentGateway yang baru dan setiap value yang sebelumnya disimpan (dalam hal ini discount) akan ikut terhapus.

Solusi dari permasalahan ini adalah dengan mengganti method `bind()` menjadi `singleton()` yang mana akan membuat kita menggunakan class dengan inisalisasi yang sama walaupun dipanggil di tempat yang berbeda.

``` php
public function register()
    {
        $this->app->singleton(PaymentGateway::class, function($app) {
            return new PaymentGateway('idr');
        });
    }

```

Mari kita buat implementasi bilamana terdapat lebih dari satu PaymentGateway, misal kita buat BankPaymentGateway dan CreditPaymentGateway. Sebelumnya, kita akan buat sebuah interface sebagai role untuk setiap jenis PaymentGateway yang akan dibuat, untuk interface-nya kita beri nama 
**PaymentGatewayContract.php**

```php

<?php

namespace App\Billing;

interface PaymentGatewayContract {

    public function setDiscount($amount);
public function charge($amount);

}

```
Setelah itu kita refactor class **PaymentGateway** menjadi **BankPaymentGateway**

```php

<?php

namespace App\Billing;

use Illuminate\Support\Str;

class BankPaymentGateway implements PaymentGatewayContract {

    private $currency;
    private $discount;
    
    public function __construct($currency)
    {
        $this->currency = $currency;
        $this->discount = 0;
}

    public function setDiscount($amount) {
        $this->discount = $amount;  
}

    public function charge($amount) {
        // Charge the bank

        return [
            'amount' => $amount - $this->discount,
            'confirmation_number' => Str::random(),
            'currency' => $this->currency,
            'discount' => $this->discount
        ];
    }
}

```

Lalu, kita buat hal serupa untuk **CreditPaymentGateway**

```php
<?php

namespace App\Billing;

use Illuminate\Support\Str;

class CreditPaymentGateway implements PaymentGatewayContract {

    private $currency;
    private $discount;
    
    public function __construct($currency)
    {
        $this->currency = $currency;
        $this->discount = 0;
}

    public function setDiscount($amount) {
        $this->discount = $amount;  
}



    public function charge($amount) {
        // Charge the bank
        $fees = $amount * 0.03;

        return [
            'amount' => $amount - $this->discount + $fees,
            'confirmation_number' => Str::random(),
            'currency' => $this->currency,
            'discount' => $this->discount,
            'fees' => $fees
        ];
    }
}

```
Nah, dengan cara seperti ini, class ataupun controller tidak perlu tau user menggunakan metode pembayaran apa, jadi di controller yang kita panggil bukan class PaymentGateway nya melainkan PaymentGatewayContract-nya.

```php
<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Billing\PaymentGatewayContract;
use App\Orders\OrderDetails;
use Illuminate\Http\Request;

class PayOrderController extends Controller
{
    public function store(OrderDetails $orderDetails, PaymentGatewayContract $paymentGateway) {
        $order = $orderDetails->all();
        dd($paymentGateway->charge(2500));
    }
}

```

Begitu juga pada class OrderDetails

```php

<?php

namespace App\Orders;

use App\Billing\PaymentGateway;
use App\Billing\PaymentGatewayContract;

class OrderDetails {

    private $paymentGateway;

    public function __construct(PaymentGatewayContract $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway; 
}

public function all() {

        $this->paymentGateway->setDiscount(500);

        return [
            'name' => 'Linux Hirarki',
            'address' => 'Semarang, Indonesia'
        ];
    }
}

```
Setelah itu kita edit AppServiceProvider kita menjadi seperti berikut 

```php
<?php

namespace App\Providers;

use App\Billing\BankPaymentGateway;
use App\Billing\CreditPaymentGateway;
use App\Billing\PaymentGateway;
use App\Billing\PaymentGatewayContract;
use Faker\Provider\ar_SA\Payment;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentGatewayContract::class, function($app) {

            if(request()->has('credit')) {
                return new CreditPaymentGateway('idr');
            }
            return new BankPaymentGateway('idr');
        });
    }

    /**
 * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
```

![Hasil dari BankPaymentGateway](/service-container/img/bankpayment_result.png)
![Hasil dari CreditPaymentGateway](/service-container/img/creditpayment_result.png)