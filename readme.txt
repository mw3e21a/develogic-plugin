=== Develogic Integration ===
Contributors: jawnecenymieszkan
Tags: develogic, apartments, real estate, api integration, image map pro
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integracja z API Develogic - wyświetlanie ofert mieszkań, filtrowanie, sortowanie, galerie i więcej.

== Description ==

Wtyczka WordPress do integracji z API Develogic. Umożliwia wyświetlanie ofert mieszkań, filtrowanie, sortowanie, galerie zdjęć, automatyczną synchronizację kolorów w Image Map Pro i więcej.

== Features ==

* Integracja z API Develogic
* Wyświetlanie listy mieszkań z filtrowaniem i sortowaniem
* Integracja z Image Map Pro - automatyczna aktualizacja kolorów kształtów
* Galerie zdjęć z LightGallery
* Historia cen mieszkań
* Funkcja "obserwuj" (ulubione)
* Funkcja druku
* REST API endpoints
* System cache
* Responsywny design

== Installation ==

1. Skopiuj folder `develogic-wp-plugin` do katalogu `wp-content/plugins/`
2. Zmień nazwę folderu na `develogic-integration`
3. Aktywuj wtyczkę w panelu WordPress (Wtyczki → Zainstalowane wtyczki)
4. Przejdź do Develogic → Ustawienia i skonfiguruj:
   * URL bazowy API
   * Klucz API
   * Inne ustawienia według potrzeb

== Frequently Asked Questions ==

= Jak skonfigurować API? =

Przejdź do Develogic → Ustawienia i wprowadź URL bazowy API oraz klucz API otrzymany od konsultanta Develogic.

= Jak używać shortcodów? =

Zobacz dokumentację w pliku README.md lub APARTMENTS_LIST_USAGE.md

= Jak skonfigurować Image Map Pro? =

Zobacz dokumentację w pliku IMAGE_MAP_PRO_SETUP.md

== Changelog ==

= 2.2.0 =
* Integracja z Image Map Pro - automatyczna aktualizacja kolorów kształtów
* Scroll do mieszkania po kliknięciu w polygon
* Panel administracyjny z edytorem kolorów

= 1.1.0 =
* Nowy layout: apartments-list - katalogowy widok listy mieszkań
* Integracja z Shuffle.js do filtrowania i sortowania
* Integracja z Tippy.js dla tooltipów
* Pełna dokumentacja w APARTMENTS_LIST_USAGE.md

= 1.0.0 =
* Pierwsza wersja
* Integracja z API Develogic
* Layout A1 dla JeziornaTowers i OstojaOsiedle
* Shortcody dla różnych widoków
* REST API
* System cache

== Upgrade Notice ==

= 2.2.0 =
Aktualizacja zalecana - dodano integrację z Image Map Pro i wiele ulepszeń.

== Screenshots ==

1. Lista mieszkań z filtrowaniem
2. Panel ustawień wtyczki
3. Integracja z Image Map Pro

