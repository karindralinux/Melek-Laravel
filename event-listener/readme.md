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
