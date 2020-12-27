# Queue

Server dalam menanggapi request dari client, caranya bisa bermacam-macam. Dari segi tempat dieksekusinya sebuah proses ada 2 cara.

Pertama, dengan melakukannya di front-layer dimana disitu adalah letak pemrosesan utama. Jadi, semisal user sudah melakukan suatu proses misal saja mengupload file, user tidak dapat menggunakan website tersebut untuk keperluan lain, misal menghapus file, mengubah file, dsb, sebelum proses pertama yakni mengupload file selesai. Cara ini disebut dengan proses synchronous.

Kedua, dalam bentuk running in the background atau di back-layer. Tempat eksekusi ini berbeda dengan layer utama atau front-layer, sehingga memungkinkan untuk suatu proses dapat dijalankan tidak harus "saat itu juga" atau bisa jadi bersamaan dengan dilakukannya aktivitas lain. 

Misalnya, pada layanan streaming musik, disamping mendengarkan musik, kita juga masih dapat melakukan aktivitas lain, seperti menambahkan lagu pada playlist, melakukan pencarian lagu, dan sebagainya. Atau misalnya pada media sosial, ketika kita menekan tombol like pada beberapa postingan dalam kurun waktu yang dekat, kita tidak perlu menunggu postingan pertama untuk data like-nya terkirim ke server lantas kita baru bisa melanjutkan scrolling timeline dan melakukan like pada postingan berikutnya. Untuk metode seperti ini, disebut dengan proses asynchronous.

## Apa itu Queue ?
-------------------------

Queue adalah konsep yang diterapkan dalam proses asynchronous, dimana setiap proses akan masuk dalam "antrian" untuk dieksekusi di belakang layar. Untuk waktu pengeksekusiannya kapan, itu tergantung dari konfigurasi dari worker yang merupakan pengeksekusi dari antrian proses tersebut.

Untuk memahami konsep queue, saya kira penting untuk sebelumnya terlebih dahulu paham tentang cara kerja [event dan listener](https://github.com/karindralinux/Melek-Laravel/tree/main/event-listener), karena untuk menjalankan queue ini tidak terlepas dari dua hal tersebut.



## Kapan harus menggunakan Queue?
-------------------------

Bayangkan, ketika pada setiap lini pemrosesan data yang ada di website harus dikerjakan saat itu juga, hmm..

Misal, saat mengganti foto profile, biasanya yang dilakukan website bukan langsung begitu saja mengupload file dan menyimpannya ke database, tapi juga perlu melewati proses image resizing atau compressing supaya juga ada efisiensi penggunaan database. Nah, jika proses terse ut sepenuhnya dikerjakan secara synchronous, maka tentu akan membuat loading yang cukup lama.

Bahkan, interaksi dengan database pun sebenarnya membutuhkan waktu, tidak bisa kita memaksakan segala request yang masuk saat itu juga langsung diarahkan ke database, jika request nya sedikit mungkin tidak masalah, yang jadi masalah bila ada banyak sekali request yang masuk. Untuk mengatasi itu, biasanya yang dilakukan adalah melakukan perubahan secara lokal terlebih dahulu, misal pada like sebuah postingan, like sudah terhitung bertambah pada orang yang mengklik, tapi belum tentu data like tersebut sudah tersimpan di database, bisa jadi baru akan masuk ke database beberapa saat kemudian.

Jadi, kapan menggunakan queue ini? Sebenarnya tergantung dari seberapa penting sebuah proses dan seberapa mampu server kita menangani sebuah request, queue ini pun digunakan untuk kenyamanan user juga, jika proses yang dilakukan tidak menimbulkan masalah jika dilakukan secara synchronous maka lebih baik menggunakan cara itu, disebabkan proses asynchronous juga akan menyebabkan penambahan beban memori yang tidak sedikit bila kita salah dalam memanagemen prosesnya.

## Mengirim Email lewat Queue
-------------------------

Sebelum langsung mengimplementasikan queue untuk mengirim email, coba kita misalkan web yang akan kita buat ini akan melakukan proses yang akan memakan waktu yang cukup lama di sisi user (selain proses mengirim email), misalkan saja image resizing, biasanya ini membutuhkan waktu yang lama. Oleh karena kita tidak tentu saja tidak akan membuat proses image resizing, maka dari itu kita misalkan proses yang lama itu pada sebuah function sleep() yang akan memberikan jeda sekian detik untuk kemudian menjalankan proses selanjutnya

`sleep(10);`

## Mengimplementasikan Queue
-------------------------

Untuk dapat membuat sebuah perintah menjadi queue dan berjalan di background, terlebih dahulu kita pastikan bahwa perintah itu adalah berupa listener (bagi kawan - kawan yang belum terlalu paham dengan listener, saya sarankan untuk memulai terlebih dahulu mempelajari tentang Event dan Listener). Kemudian, setelah itu class listener itu kita implements interface ShouldQueue, seperti berikut

```php
<?php

namespace App\Listeners;

use App\Mail\WelcomeNewUserMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class WelcomeNewUserListener implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        sleep(10);
        // Mengirim Email Selamat Datang
        Mail::to($event->user['email'])->send(new WelcomeNewUserMail());
    }
}
```
Hanya dengan menambahkan ShouldQueue, listener kita sudah terdaftar sebagai queue yang akan dijalankan di background.

## Mengatur konfigurasi Queue Driver
-------------------------

Driver yang disediakan oleh laravel dalam urusan queue ini ada beberapa macam, kawan-kawan dapat melihatnya di config/queue.php

```php
    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */
```

Disitu tertera ada beberapa pilihan driver seperti sync, database, redis, sqs, dll, yang masing-masing dapat dikustomisasi sesuai kebutuhan.

- **sync** : tidak terjadi proses in the background, semua berjalan secara normal dan dikerjakan secara synchronous
- **database** : menyimpan daftar queue di database
- **redis** : menyimpan daftar queue di redis sebagai memory database (biasa digunakan di production)

Untuk studi kasus kali ini kita akan menggunakan database sebagai queue driver-nya. Untuk setup QUEUE_CONNECTION dapat dilakukan di .env

`QUEUE_CONNECTION=database`

## Membuat Migrasi Untuk Tabel Queue Jobs
-------------------------

Berhubung driver yang kita gunakan adalah database, maka list queue yang masuk akan disimpan terlebih dahulu ke database, sebelum nantinya dijalankan. Maka dari itu, kita memerlukan tabel khusus untuk menyimpan queue. Laravel menyediakan command untuk membuat migrasi dari tabel ini.

`php artisan queue:table`

Dari command tersebut, akan menghasilkan file migration yang akan membuat tabel bernama jobs

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
```

## Menjalankan Queue
-------------------------

Setelah semua konfigurasi siap, untuk menjalankan queue kita dapat menggunakan perintah 

`php artisan queue:work`

Sebagai alternatif, kita juga dapat menggunakan perintah `queue:listen`. Jika menggunakan ini, kita tidak perlu merestart worker jika ada perubahan pada kode, akan tetapi penggunaaan perintah ini tidak lebih efisien dari perintah `queue:work`

`php artisan queue:listen`

Ketika listener dijalankan seharusnya, akan muncul log seperti berikut 

`[2020-12-27 15:02:57][3] Processing: App\Listeners\WelcomeNewUserListener`
`[2020-12-27 15:03:35][3] Processed:  App\Listeners\WelcomeNewUserListener`

Yeay, queue succesfully running !

# Menjalankan Queue:work di background
-------------------------

Ketika kita menjalankan queue:work pada umumnya, terminal akan terkunci hanya untuk proses tersebut dan tidak dapat melakukan proses lain. Sebenarnya, hal ini dapat diatasi dengan membuka tab terminal baru, tapi bagi kawan-kawan yang tidak mau melakukan itu, dapat mencoba cara ini


 `php artisan queue:work > storage/logs/jobs.log &`


Perintah tersebut akan menjalankan queue secara background dan kita dapat melihat lognya pada file **jobs.log**.

Untuk mengecek proses queue sudah berjalan atau belum, dapat menggunakan perintah 

`jobs -l`

Maka, seharusnya output yang muncul adalah seperti ini

`[1]+  9776 Running     php artisan queue:work > storage/logs/jobs.log &`            

4 digit angka itu menunjukkan process id dari worker tersebut, yang dapat kita gunakan ketika ingin mematikkannya, melalu perintah seperti berikut	

`KILL 9776`







