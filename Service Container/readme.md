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