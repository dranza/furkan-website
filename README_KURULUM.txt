FurkanCihan.com.tr - Dinamik PHP Portföy + Blog + Projeler (Admin Panelli)
=======================================================================

Bu paket, paylaşımlı hostinglerde (cPanel vb.) PHP 8+ ve MySQL ile çalışacak şekilde hazırlanmıştır.
Tamamen dinamik içerik yönetimi için admin panel içerir.

1) Gereksinimler
---------------
- PHP 8.0+ (önerilen 8.1/8.2)
- MySQL 5.7+ / MariaDB 10+
- mod_rewrite açık (SEO URL için)
- Hostingde bir MySQL veritabanı ve kullanıcı oluşturulmuş olmalı.

2) Yükleme
----------
a) Zip içeriğini hosting kök dizinine (public_html) yükleyin.
b) Tarayıcıdan şu adrese gidin:
   https://furkancihan.com.tr/install/
c) Kurulum sihirbazı:
   - DB Host / DB Name / DB User / DB Pass
   - Site URL (örn: https://furkancihan.com.tr)
   - Admin kullanıcı adı ve şifre
d) Kurulum tamamlandıktan sonra, güvenlik için /install klasörünü SİLİN.

3) Admin Panel
--------------
Giriş: https://furkancihan.com.tr/admin/login.php

4) İçerikler
------------
- Hakkımda: Eğitim, Deneyim, Genel açıklama (admin panelden)
- Blog: Kapak görseli, etiket, SEO başlık/açıklama, taslak/yayın
- Projeler: Teknolojiler, SEO, taslak/yayın

5) SEO
------
- SEO dostu URL: /blog/slug ve /proje/slug
- robots.txt ve sitemap.xml dinamik üretilir
- Sayfalarda meta title/description alanları desteklenir

6) Güvenlik Notları
-------------------
- Admin şifreleri password_hash ile saklanır.
- Admin formlarında CSRF koruması bulunur.
- uploads klasöründe PHP çalıştırma engellenir.

İyi kullanımlar! 

GÜNCELLEME (v2)
---------------
- Admin panel okunabilirlik iyileştirildi (dark UI).
- Öne Çıkan Projeler (featured): Proje düzenle ekranından 'Öne Çıkan' seç.
- Blog filtreleri: Kategori / Etiket / Arama
- Proje filtreleri: Teknoloji / Arama
- Medya Kütüphanesi: Admin > Medya (yükle + link kopyala + editörde seç)

Not: Eski kurulumlarda sistem otomatik olarak veritabanını günceller (schema migration).

V6 ENTERPRISE
-------------
- Yayın planlama: published_at geleceğe ayarlanırsa içerik otomatik o tarihte görünür.
- Revizyon geçmişi: Blog/Proje düzenle ekranından Revizyonlar -> Geri al.
- Analytics: Admin -> Analytics (grafik + top sayfalar + referrer).
- Opsiyonel: GA Measurement ID ve Search Console meta doğrulaması Site Ayarları'na eklendi.

V7 SEO + YORUM MODERASYON
------------------------
- Sitemap otomatik güncellenir (Site Ayarları -> SEO & Sitemap).
- Ping: Google/Bing sitemap ping denemesi (opsiyonel).
- robots.txt otomatik yazılır (Sitemap oluşturulunca).
- Blog yorumları: Site Ayarları -> Yorumlar. Varsayılan: onay sonrası yayın.
- Admin -> Yorumlar: moderasyon (Onayla/Spam/Sil).

V7.1 HOTFIX
-----------
- CSRF::check artık exit etmez (boolean döner). Admin tarafı CSRF::checkOrExit kullanır.
- Yorum gönderiminde CSRF token geçersiz olsa bile 403 vermez (session kaybı kaynaklı sorunlar için).
- Admin tabloları: beyaz arka plan/okunmayan yazılar için koyu tema zorlandı.
- Anasayfa: Öne Çıkan Projeler + Neler Yapıyorum kartları eklendi.

V7.2 UI HOTFIX
--------------
- Admin CKEditor yazı alanı: beyaz zemin + koyu yazı (okunabilirlik düzeltildi).
- Anasayfa: Neler Yapıyorum? bölümü tekilleştirildi ve düzen temizlendi.

V8 SEO BOOST + PROFIL FOTO + 2FA
--------------------------------
- OG/Twitter meta + canonical + noindex(filtreli liste) + JSON-LD schema.
- Blog/Proje: İçindekiler (TOC) + okuma süresi + benzer içerikler.
- Admin: Profil fotoğrafı yükleme (Site Ayarları), 2FA (Güvenlik), Takvim (planlı yayınlar), Yedek (SQL export).


V9 FULL PACK
-----------
- Paylaşım butonları: WhatsApp + X + Instagram + Link kopyala.
- İletişim formu + Admin mesajlar.
- Kullanıcı rolleri: admin/editor + kullanıcı yönetimi.
- Kod highlight + kopyala butonu.
- Proje/Blog şablon ekleme.


V10 UI UPGRADE
-------------
- Anasayfa: Yaklaşım, Timeline, Geri Bildirimler bölümleri.
- İletişim: Kurumsal hero + modern form tasarımı.
- Admin: testimonials ayarı.


V10.2 UI FIX + CONTACT PRO
-------------------------
- Anasayfa: profil foto URL fix + tam yuvarlak avatar.
- İletişim: mesaj alanı kesin görünür (Contact çağrısı view'den alındı).
- İletişim: çalışma saatleri + uygunluk rozeti + hızlı teklif checkbox + harita embed.
- Admin: contact_hours/contact_availability/contact_map_embed alanları.


V10.3 HOTFIX
------------
- Profil fotoğrafı: Medya seçimi (input profile_photo) artık Settings'e kaydedilir.


V10.4 NAVBAR BRAND
-----------------
- Üst menüde logo/monogram + site adı + slogan.
- Slogan admin/site-ayarlar.php: brand_tagline.
- Logo olarak profile_photo kullanılır.


V10.4.2 HOTFIX
--------------
- Timeline::all() eklendi (anasayfa timeline preview için).
- $noindex default tanımlandı (compact uyarısı kaldırıldı).


V10.4.3 HOTFIX
--------------
- Admin yedek: header already sent hatası düzeltildi (download işlemi HTML'den önce çalışır).


V11 ENTERPRISE
-------------
- Tam yedek (ZIP): public_html + database.sql tek tık.
- Performans: sayfa cache + gzip/expires + lazyload.
- Güvenlik: admin login rate limit.
- Hata log: storage/php-error.log içine yönlendirme.
- Kullanıcı kayıt/giriş/profil sistemi (+ admin onay).
- Yorumlar: isteğe bağlı giriş zorunluluğu.


V11.4
-----
- Kullanıcı rolü (user) siteye içerik müdahalesi yapamaz.
- /destek : ticket oluşturma + ticket listesi
- /destek/{id} : ticket detay + mesajlaşma
- Admin > Ticket: ticket yönetimi + yanıt + durum


V11.5
-----
- Güvenlik: Admin girişinde sadece admin/editor rolleri kabul edilir (user admin paneline giremez)
- Profil sayfasında: Kullanıcının blog yorumları listelenir (onay durumuyla)
- Kayıtlar: role=user + (opsiyonel) admin onayı (Site Ayarları > Kayıt onayı)


Beceriler & Sertifikalar
------------------------
- Admin panel: Beceriler ve Sertifikalar menülerinden ekleyebilirsin.
- Hakkımda sayfasında otomatik görünür.
- Sertifika logosu için Medya'dan yükleyip dosya yolunu (örn: /uploads/media/xxx.jpg) ekle.
