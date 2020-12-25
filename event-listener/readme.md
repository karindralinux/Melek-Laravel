# Event dan Listener

Dalam proses sebuah website bekerja dapat terjadi banyak hal, misal saja dalam satu kali klik button register user, sebuah website dapat menjalankan beberapa hal seperti misalnya mengirim email konfirmasi ke user, mengirim notifikasi ke admin, dan mengirim email penawaran berlangganan.

## Apa itu Event dan Listener?
-------------------------

Seperti halnya ketika user melakukan registrasi, mengupload foto profil, menambahkan data baru, dan semacamnya itulah yang dikenal dengan sebutan Event.

Event adalah keadaan yang akan mentrigger handling dari keadaan tersebut, handling itulah yang dikenal dengan sebutan Listener.

Misal, ada event ketika user melakukan registrasi, maka event tersebut akan memanggil listener listener yang bersangkutan, contohnya listener untuk mengirim email konfirmasi, listener untuk mengirim notifikasi ke admin, dsb.

## Membuat Event dan Listener Ketika User Melakukan Registrasi
-------------------------

Mari kita coba buat studi kasus, misalnya ketika user melakukan registrasi, selain tentu saja menyimpan data user ke database, website akan mengirim email selamat datang ke user, mengirim notifikasi ke admin, dan mendaftarkan user ke newsletter. Tentu saja, beberapa hal tersebut kita lakukan secara simpel dan dalam bentuk pseudo code saja, yang terpenting dari situ kita dapat memahami bagaimana event dan listener ini bekerja.

**RegisterController.php**

```php
<?php

namespace App\Http\Controllers;

use App\Events\NewUserRegisteredEvent;
use App\Mail\WelcomeNewUserMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    //

    public function register() {

        // Memasukkan data user ke database

        // User::create($this->validateRequest());

        $user = [
            'nama' => "Karindra Linux",
            'email' => 'namakulinux@gmail.com',
            'address' => 'Semarang, Indonesia'
        ];

        // Mengirim Email Selamat Datang
       	Mail::to($user['email'])->send(new WelcomeNewUserMail()); 
        // Mengirim notifikasi ke admin
        dump('Mengirim notifikasi ke admin..');
        // Mendaftarkan ke newsletter
        dump('Terdaftar dalam newsletter');
        // return redirect('/');
    }
}
```

Bisa dilihat kode diatas memang tidak sepenuhnya bekerja sebagaimana seharusnya, ada beberapa yang hanya saya comment dan juga ada yang dalam bentuk log saja.

Untuk step mengirim email bagi kawan-kawan yang belum paham dapat melihat tulisan saya lainnya yang membahas tentang Mengirim Email di Laravel.

Oke, jadi intinya dalam satu kali register ada beberapa proses yang website kita lakukan :

1.Insert data user ke database
2.Mengirim email selamat datang
3.Mengirim notifikasi ke admin
4.Mendaftarkan ke newsletter

Salah satu manfaat dari diterapkannya Event dan Listener ini adalah membuat controller kita menjadi sebersih, seramping, dan sesimpel mungkin, jadi tidak terlampau banyak kode kode yang mendetail dari setiap proses yang akan dilakukan. 

Untuk proses insert data user ke db lazimnya memang dilakukan langsung di controller, tapi tidak menutup kemungkinan bila ingin melakukan prosesnya di listener. Namun, untuk studi kasus kita kali ini, kita tidak membuat proses insert data user ke db di listener.

Jika kode sebelumnya dijalankan maka, hasilnya akan seperti berikut

![](/event-listener/img/screenshoot_web.png)
![](/event-listener/img/screenshoot_email.png)

### Membuat Event

Seperti yang pernah kita bahas sebelumnya, event bertugas sebagai suatu “Pemanggil” dan listener yang akan “Mendengar” dan menanggapinya sebagai sebuah aksi. 

`php artisan make:event NewUserRegisteredEvent`

Event hanya bertugas mentrigger listener seperti halnya memanggil “Hai, ada user baru!” dan listener yang bersangkutan akan menanggapinya sesuai tugasnya masing-masing.

Untuk menggunakan event kita dapat menuliskannya seperti ini, dan memasukkan argument yang diinginkan

`event(new NewUserRegisteredEvent($user));`

Lalu dalam constructor method pada event yang kita buat kita tambahkan parameter, sekaligus kita set property-nya seperti berikut :

**NewUserRegisteredEvent.php**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewUserRegisteredEvent
{
use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
```

Alasan kita menggunakan access modifier public pada variable user adalah agar variable ini dapat dengan mudah diakses oleh listener nanti.

### Membuat Listener

Listener akan berisi handling dari keadaan yang diinginkan, seperti misalnya mengirim email, mengirim notifikasi, dsb. Dalam satu event dapat terdiri dari beberapa listener, dan tidak ada batasan bila listener yang sama juga digunakan di event yang lain.

`php artisan make:listener WelcomeNewUserListener`

Oke, pada listener kali ini kita buat untuk memproses pengiriman email selamat datang.

**WelcomeNewUserListener.php**

```php
<?php

namespace App\Listeners;

use App\Mail\WelcomeNewUserMail;
use Illuminate\Support\Facades\Mail;

class WelcomeNewUserListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // Mengirim Email Selamat Datang
      Mail::to($event->user['email'])->send(new WelcomeNewUserMail());
    }
}
```

Method `handle()` menerima parameter berupa event, berhubung tadi pada event property $user kita set public maka kita dapat dengan mudah mengakses $user tersebut.

### Menghubungkan Event dengan Listener

Untuk menghubungkan event dengan listener kita perlu menambahkannya di **EventServiceProvider.php**.

```php
<?php

namespace App\Providers;

use App\Events\NewUserRegisteredEvent;
use App\Listeners\WelcomeNewUserListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        NewUserRegisteredEvent::class => [
            WelcomeNewUserListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        //
    }
}
```

### Mengenerate Event dan Listener

Selain dengan cara manual, kita dapat dengan mudah mengenerate event dan listener yang belum ada tetapi sudah terdaftar di EventServiceProvider. Sebelumnya buat dulu sketsa dari event dan listener yang akan kita buat.

```php
<?php

namespace App\Providers;

use App\Events\NewUserRegisteredEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        NewUserRegisteredEvent::class => [
            \App\Listeners\WelcomeNewUserListener::class,
            \App\Listeners\SendNotificationToAdmin::class,
            \App\Listeners\RegisterCustomerToNewsletter::class,
        ],
    ];
    
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        //
    }
}
```

Untuk penulisan daftar listener, kita diharuskan untuk menuliskan path nya secara lengkap seperti pada kode di atas. Setelah itu kita dapat mengenerate-nya dengan perintah berikut :

`php artisan event:generate`

Setelah itu kita modifikasi listener kita yang baru, menjadi seperti berikut 

**RegisterCustomerToNewsletter.php**
```php
<?php

namespace App\Listeners;

use App\Events\NewUserRegisteredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RegisterCustomerToNewsletter
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
}

    /**
     * Handle the event.
     *
     * @param  NewUserRegisteredEvent  $event
     * @return void
     */
    public function handle(NewUserRegisteredEvent $event)
    {
        // Mendaftarkan ke newsletter
        dump('Terdaftar dalam newsletter');
    }
}
```

**SendNotificationToAdmin.php**
```php
<?php

namespace App\Listeners;

use App\Events\NewUserRegisteredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationToAdmin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
}

    /**
     * Handle the event.
     *
     * @param  NewUserRegisteredEvent  $event
     * @return void
     */
    public function handle(NewUserRegisteredEvent $event)
    {
        // Mengirim notifikasi ke admin
        dump('Mengirim notifikasi ke admin..');
    }
}
```

Jika kita lihat RegisterController-nya seharusnya sudah lebih rapi dan simpel

```php
public function register() {

        // Memasukkan data user ke database
        // User::create($this->validateRequest());
        $user = [
            'nama' => "Karindra Linux",
            'email' => 'namakulinux@gmail.com',
            'address' => 'Semarang, Indonesia'
        ];
        event(new NewUserRegisteredEvent($user));
        // return redirect('/');
}
```

Sip, its look more simple, right? Kedepannya ketika kawan-kawan tidak lagi membutuhkan satu dua fitur, dapat mudah kita tinggal menghapus daftar listener dari EventServiceProvider, dan suatu saat bila akan dibutuhkan kita akan menuliskannya kembali.

Konsep Event dan Listener ini, kelak akan kita gunakan pada konsep Queue ketika kita dapat menjalankan suatu aksi secara bersamaan dengan aksi lain karena kita menjalankannya in the background, wow !