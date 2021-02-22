# Notification

Saat membangun backend yang terintegrasi dengan mobile, tidak sedikit studi kasus yang menerapkan notifikasi. Seperti misalnya, ketika ada data baru ditambahkan, maka backend akan mengirim notifikasi bahwa ada data baru yang siap digunakan oleh user. Penerapannya bisa seperti payment, ketika ada customer yang melakukan transaksi maka admin akan mendapatkan notifikasi. Begitu juga dengan layanan video streaming, ketika channel langganan menambahkan video baru, maka akan muncul notif pemberitahuan mengenai hal itu. Contoh lain misalnya dengan mengirimkan pemberitahuan update dan meminta user untuk mengupdate aplikasi tersebut, dan beragam studi kasus lainnya. 

Notifikasi punya banyak cara untuk diterapkan, tidak harus melalui aplikasi mobile yang dibuat sendiri, kita juga dapat memanfaatkan pihak ke-3 seperti Email, Aplikasi Perpesanan, SMS, dsb. Beberapa hal tersebut akan coba saya ulas pada tulisan kali ini.


## # Membuat Notifikasi di Laravel

Cara yang akan dibahas di tulisan ini adalah cara praktisnya bila kawan-kawan ingin melihat dokumentasi lengkapnya dapat mengunjungi [dokumentasi resmi Laravel](https://laravel.com/docs/8.x/notifications)

`php artisan make:notification NewBook` 

Perintah di atas akan menghasilkan sebuah class Notification seperti berikut :

```
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBook extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

```


Di dalam class ini terdapat beberapa method utama yaitu via() dan toNamaDriver()

- *`via()`*, method ini berfungsi untuk mereturn driver apa saja yang akan kita gunakan untuk mengirim notifikasi. Driver yang dimaksud disini seperti email, telegram, webhook, database, dsb.

- *`toNamaDriver()`*, untuk method yang ini namanya berbeda-berbeda tergantung pada driver yang digunakan contoh: `toMail()`, `toTelegram()`, `toWebhook()`, dll. Melalui method ini kita dapat mensetup data untuk notifikasi yang akan dikirim. Secara default ketika mengenerate class Notification maka akan tersedia driver toMail() dan toArray(), ketika tidak ada driver yang dimasukkan dalam list di `via()`, maka secara default akan menjalankan `toArray()`, yang mereturn sebuah array.


## # Driver Mail

1. Pertama kita perlu setup dulu config mail sesuai layanan email yang kita gunakan. Kita dapat mengaturnya melalui `.env`

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"
```

2. Lalu tambahkan driver mail pada method `via()` seperti berikut

```
public function via($notifiable)
{
    return ['mail'];
}
```

3. Setelah itu pada bagian method `toMail()` tambahkan beberapa baris kode untuk mengatur notifikasi yang akan dikirim. Secara default method ini akan berisi barisan kode berikut 


```

public function toMail($notifiable)
{
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
}


```

> Driver bawaan laravel memang terbatas, maka dari itu 
> ketika kita menggunakan pihak ketiga, kita dapat 
> membuat customan channel notifikasi untuk driver 
> tertentu. Selengkapnya dapat dilihat di [Custom Channel Laravel](https://laravel.com/docs/8.x/notifications#custom-channels)


##  # Driver Telegram 
Untuk mempermudah proses development kita dapat menggunakan package untuk driver notification telegram, sehingga kita tidak perlu untuk mengcustom Channel nya

package yang akan kita gunakan yaitu https://github.com/laravel-notification-channels/telegram . Konfigurasinya seperti berikut 

1. Jalankan `composer require laravel-notification-channels/telegram`

2. Masukkan `TELEGRAM_BOT_TOKEN` di config/services.php

```
'telegram-bot-api' => [
    'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE')
],

```

3. Sekarang kita dapat menambahkan driver telegram dari package ini dengan menambahkan TelegramChannel, seperti berikut

```
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }


    public function toTelegram($notifiable) {
        return TelegramMessage::create()
                ->content("Buku berjudul ". $this->book->title. " berhasil ditambahkan");
    }
```



## # Driver Webhook (Whatsapp via Wafvel.com)

- Untuk mempermudah proses development, dapat menggunakan package berikut https://github.com/laravel-notification-channels/webhook

1. `composer require laravel-notification-channels/webhook`

2. Setelah itu tambahkan channel dan setup method `toWebhook()`

```
use NotificationChannels\Webhook\WebhookChannel;
use NotificationChannels\Webhook\WebhookMessage;

public function via($notifiable)
{
        return [WebhookChannel::class];
}

public function toWebhook($notifiable) {

        $date = Carbon::now()->format('Y-m-d H:i:s');

        $message = 'Buku Berjudul '. $this->book->title. ' berhasil ditambahkan'. "\n \n".'Dikirim pada : '.$date;
        
        Log::info($message);
        
        return WebhookMessage::create()
            ->data([
               'token'      =>  '571dbed6c7471408f2cd3281',
               'phone'      =>  '6281225773434',
               'message'    =>  $message
            ])
            ->header('Accept', 'application/json')
            ->header('Content-Type', 'application/json');

}

```

3. Untuk format message pada driver webhook kita sesuaikan dengan webhook yang kita gunakan masing-masing, disini saya menggunakan wafvel.com. Untuk dokumentasi API-nya bisa dicek di laman [berikut](https://documenter.getpostman.com/view/6587471/TVKHTadP)


## # Mengirim Notifikasi di Laravel

Secara default, class notification dijalankan secara `Queueable` artinya dijalankan di background. Untuk pengiriman notifikasi dapat melalui target user yang dipresentasikan sebagai model, atau bisa melalui facade laravel langsung.


- Menggunakan model

Cara ini biasanya digunakan ketika kita mengirimkan notifikasi ke user. Sebagai contoh di model User bawaan Laravel, terdapat trait `Notifiable`, nah trait ini yang memungkinkan kita dapat menggunakan perantanra model untuk mengirim notifikasi. Jadi, bila kita ingin menggunakan model untuk mengirim notifikasi terlebih dahulu kita harus mengimportkan trait tersebut dengan cara `use Notifiable`


Setelah itu, untuk proses mengirimkannya kita dapat menggunakan contoh kode seperti berikut

```
$user = User::findOrFail($id);
$user->notify(new Book($book));

```

Nah, kelak $book akan dipassing melalui constructor sedangkan $user akan dipassing melalui method-method yang ada di class Notification seperti via() dan toDriver() yang terepresentasikan menjadi $notifiable



- On-Demand Notification

Cara yang satu ini lebih fleksibel dari sebelumnya, karena tidak memerlukan model sebagai perantara untuk memanggil method `notify()` hanya tinggal menggunakan facade `Notification`.

```

Notification::route('telegram', 1238166167)
                ->route('webhook', 'https://wafvel.com:2096/api/whatsapp/async/send')
                ->notify(new NewBook($createdBook));

```

Kita dapat memilah sendiri driver apa yang akan digunakan dan target mana yang akan dikirimi notifikasi.

T**erima kasih. Semoga Bermanfaat.** Jika ada kekeliruan, silahkan bantu untuk memperbaiki dengan membuat pull request.
