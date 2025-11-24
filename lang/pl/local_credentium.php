<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Polish language strings for the local_credentium plugin.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Integracja Credentium®';
$string['privacy:metadata'] = 'Wtyczka Integracja Credentium® przechowuje lokalnie informacje o wydanych certyfikatach i przesyła dane użytkowników do zewnętrznej usługi Credentium® (płatne API strony trzeciej).';

// Local database storage.
$string['privacy:metadata:local_credentium_issuances'] = 'Informacje o certyfikatach wydanych użytkownikom.';
$string['privacy:metadata:local_credentium_issuances:userid'] = 'ID użytkownika, który otrzymał certyfikat.';
$string['privacy:metadata:local_credentium_issuances:courseid'] = 'ID kursu, dla którego wydano certyfikat.';
$string['privacy:metadata:local_credentium_issuances:credentialid'] = 'Zewnętrzny ID certyfikatu w systemie Credentium.';
$string['privacy:metadata:local_credentium_issuances:status'] = 'Status wydania certyfikatu.';
$string['privacy:metadata:local_credentium_issuances:timecreated'] = 'Czas wydania certyfikatu.';

// External service - Credentium API (PAID commercial service).
$string['privacy:metadata:credentium_api'] = 'API Credentium® to płatna usługa strony trzeciej (https://credentium.com) służąca do wydawania cyfrowych certyfikatów. Dane użytkowników są przesyłane do tej zewnętrznej usługi.';
$string['privacy:metadata:credentium_api:email'] = 'Adres e-mail użytkownika jest wysyłany do Credentium® w celu identyfikacji odbiorcy certyfikatu.';
$string['privacy:metadata:credentium_api:firstname'] = 'Imię użytkownika jest wysyłane do Credentium® w celu personalizacji certyfikatu.';
$string['privacy:metadata:credentium_api:lastname'] = 'Nazwisko użytkownika jest wysyłane do Credentium® w celu personalizacji certyfikatu.';
$string['privacy:metadata:credentium_api:coursename'] = 'Nazwa kursu jest wysyłana do Credentium® w celu identyfikacji kursu, którego dotyczy certyfikat.';
$string['privacy:metadata:credentium_api:grade'] = 'Ocena użytkownika z kursu (jeśli włączona) jest wysyłana do Credentium® w celu umieszczenia na certyfikacie.';
$string['privacy:metadata:credentium_api:templateid'] = 'ID szablonu certyfikatu jest wysyłane do Credentium® w celu określenia, który projekt certyfikatu ma zostać użyty.';

// Settings.
$string['settings'] = 'Ustawienia Credentium';
$string['globalsettings'] = 'Ustawienia globalne Credentium';
$string['settings_desc'] = 'Skonfiguruj połączenie z API Credentium do wydawania mikrokwalifikacji.';
$string['enabled'] = 'Włącz integrację Credentium';
$string['enabled_desc'] = 'Włącz lub wyłącz integrację Credentium globalnie.';
$string['enabled_help'] = 'Po włączeniu wtyczka będzie automatycznie wydawać cyfrowe certyfikaty studentom po ukończeniu kursu (jeśli kurs jest również skonfigurowany indywidualnie).';
$string['apiurl'] = 'URL API';
$string['apiurl_desc'] = 'Adres URL punktu końcowego API Credentium.';
$string['apiurl_global'] = 'URL API';
$string['apiurl_global_help'] = 'Adres URL punktu końcowego API Credentium (np. https://api.credentium.com). Jest używany domyślnie, gdy tryb kategorii jest wyłączony lub gdy kursy nie mają poświadczeń specyficznych dla kategorii.';
$string['apikey'] = 'Klucz API';
$string['apikey_desc'] = 'Twój klucz API Credentium do uwierzytelniania.';
$string['apikey_global'] = 'Klucz API';
$string['apikey_global_help'] = 'Twój klucz API Credentium do uwierzytelniania. Klucz jest przechowywany w zaszyfrowanej postaci w bazie danych.';
$string['testconnection'] = 'Testuj połączenie';
$string['testconnection_heading'] = 'Test połączenia';
$string['testconnection_desc'] = 'Po wprowadzeniu URL API i klucza API powyżej, kliknij przycisk poniżej, aby przetestować połączenie z API Credentium.';
$string['testconnection_disabled'] = 'Zapisz URL API i klucz API przed testowaniem połączenia.';
$string['connectionsuccessful'] = 'Połączenie z API Credentium powiodło się!';
$string['connectionfailed'] = 'Połączenie z API Credentium nie powiodło się: {$a}';

// Multi-tenant configuration.
$string['categorymode_heading'] = 'Konfiguracja wielodostępowa';
$string['categorymode_heading_desc'] = 'Włącz poświadczenia API dla każdej kategorii dla wdrożeń wielodostępowych.';
$string['categorymode'] = 'Włącz tryb kategorii';
$string['categorymode_desc'] = 'Po włączeniu możesz skonfigurować osobne poświadczenia API dla każdej kategorii kursów, umożliwiając wdrożenia wielodostępowe, gdzie różne kategorie łączą się z różnymi instancjami Credentium. Po wyłączeniu wszystkie kursy używają globalnych poświadczeń API skonfigurowanych powyżej.';
$string['categorymode_help'] = 'Po włączeniu możesz skonfigurować osobne poświadczenia API dla każdej kategorii kursów, umożliwiając wdrożenia wielodostępowe, gdzie różne kategorie łączą się z różnymi instancjami Credentium. Link "Ustawienia Credentium kategorii" pojawi się na stronie ustawień każdej kategorii.';
$string['categorymode_info_heading'] = 'Tryb kategorii jest włączony';
$string['categorymode_info_text'] = 'Musisz skonfigurować poświadczenia API dla każdej kategorii kursów osobno. Przejdź do dowolnej kategorii kursów i uzyskaj dostęp do <strong>Ustawień Credentium kategorii</strong>, aby skonfigurować URL API i klucz API dla tej kategorii. Poniższe poświadczenia globalne są opcjonalne i będą używane jako zapasowe, gdy nie są skonfigurowane poświadczenia specyficzne dla kategorii.';

// Debug logging.
$string['debuglog_heading'] = 'Logowanie debugowania';
$string['debuglog_heading_desc'] = 'Skonfiguruj logowanie debugowania w celu rozwiązywania problemów i rozwoju.';
$string['debuglog'] = 'Włącz logowanie debugowania';
$string['debuglog_desc'] = 'Po włączeniu wtyczka będzie zapisywać szczegółowe informacje diagnostyczne w dzienniku błędów PHP. Jest to przydatne do rozwiązywania problemów, ale powinno być wyłączone w środowiskach produkcyjnych w celu zmniejszenia objętości dziennika. Logi są zapisywane w dzienniku błędów serwera WWW (zazwyczaj /var/log/apache2/error.log lub /var/log/php-fpm/error.log).';
$string['debuglog_help'] = 'Po włączeniu szczegółowe informacje diagnostyczne są zapisywane w dzienniku błędów PHP (zazwyczaj /var/log/apache2/error.log lub /var/log/php-fpm/error.log). Przydatne do rozwiązywania problemów, ale powinno być wyłączone w środowiskach produkcyjnych w celu zmniejszenia objętości dziennika.';

// Data retention (GDPR).
$string['dataretention'] = 'Okres przechowywania danych';
$string['dataretention_help'] = 'Jak długo przechowywać rekordy wydanych certyfikatów w bazie danych przed automatycznym usunięciem. Rekordy starsze niż ten okres będą trwale usunięte przez zaplanowane zadanie, które działa codziennie o 2:00 w nocy (czas serwera). Zapewnia to zgodność z RODO poprzez wdrażanie zasad minimalizacji danych. Domyślnie: 365 dni (1 rok). Dostępne jednostki: dni, tygodnie. Uwaga: Ponieważ czyszczenie działa codziennie, rzeczywiste przechowywanie może być do 24 godzin dłuższe niż skonfigurowane. Usuwanie dotyczy wszystkich rekordów wydanych certyfikatów niezależnie od statusu (wydane, nieudane lub oczekujące).';

// Course settings.
$string['coursesettings'] = 'Ustawienia Credentium';
$string['courseenabled'] = 'Włącz Credentium dla tego kursu';
$string['courseenabled_help'] = 'Po włączeniu studenci otrzymają certyfikaty Credentium po ukończeniu kursu.';
$string['credentialtemplate'] = 'Szablon certyfikatu';
$string['credentialtemplate_help'] = 'Wybierz szablon certyfikatu do użycia w tym kursie.';
$string['templaterequiresgrade'] = 'Ten szablon wymaga oceny';
$string['templaterequiresgrade_info'] = 'Ten szablon certyfikatu zawiera twierdzenie(-a) oceny uczenia się, dlatego ocena studenta z kursu zostanie umieszczona na mikrokwalifikacji. Wydanie certyfikatu nie powiedzie się, jeśli ocena nie będzie dostępna.';
$string['templatenograderequired'] = 'Ten szablon nie zawiera twierdzeń oceny';
$string['templatenograderequired_info'] = 'Ten szablon certyfikatu nie zawiera twierdzeń oceny uczenia się, więc ocena z kursu (nawet jeśli dostępna) nie zostanie przedstawiona na mikrokwalifikacji.';
$string['inherit_category'] = 'Użyj poświadczeń API kategorii';
$string['inherit_category_help'] = 'Po włączeniu ten kurs będzie używał poświadczeń API skonfigurowanych na poziomie kategorii (lub poświadczeń globalnych, jeśli nie znaleziono konfiguracji kategorii). Po wyłączeniu musisz skonfigurować poświadczenia API specyficzne dla kursu.';
$string['categoryinfo'] = 'Źródło poświadczeń API';
$string['categoryinfo_global'] = 'Ten kurs będzie używał <strong>globalnych poświadczeń API</strong> skonfigurowanych w ustawieniach wtyczki.';
$string['categoryinfo_inherited'] = 'Ten kurs będzie używał poświadczeń API z kategorii <strong>{$a}</strong>.';
$string['unknowncategory'] = 'Nieznana kategoria';
$string['nocredentials_heading'] = 'Integracja Credentium nie jest skonfigurowana';
$string['nocredentials_message'] = 'Integracja Credentium nie może być włączona dla tego kursu, ponieważ nie skonfigurowano poświadczeń API. Skontaktuj się z administratorem witryny, aby skonfigurować poświadczenia API Credentium globalnie (Administracja witryny > Wtyczki > Wtyczki lokalne > Credentium) lub dla kategorii tego kursu (Kategoria > Ustawienia Credentium kategorii).';
$string['category_credentium_disabled_heading'] = 'Credentium wyłączone dla tej kategorii';
$string['category_credentium_disabled'] = 'Integracja Credentium jest wyłączona dla kategorii "{$a}". Skontaktuj się z administratorem witryny, aby włączyć Credentium dla tej kategorii w: Kategoria > Ustawienia Credentium kategorii.';

// Category settings.
$string['categorysettings'] = 'Ustawienia Credentium kategorii';
$string['categorysettings_desc'] = 'Skonfiguruj Credentium dla tej kategorii. Wszystkie kursy w tej kategorii (i jej podkategoriach) odziedziczą te ustawienia.';
$string['category_enable_credentium'] = 'Włącz Credentium dla tej kategorii';
$string['category_enable_credentium_help'] = 'Po włączeniu integracja Credentium staje się dostępna dla wszystkich kursów w tej kategorii i jej podkategoriach. Po wyłączeniu kursy w tej kategorii nie mogą używać Credentium (nawet jeśli są skonfigurowane na poziomie globalnym).';
$string['credentialsource'] = 'Źródło poświadczeń API';
$string['credentialsource_help'] = 'Wybierz, czy używać globalnych poświadczeń API skonfigurowanych na poziomie witryny, czy skonfigurować niestandardowe poświadczenia API specyficzne dla tej kategorii.';
$string['credentialsource_global'] = 'Użyj globalnych poświadczeń API';
$string['credentialsource_custom'] = 'Użyj niestandardowych poświadczeń API dla tej kategorii';
$string['globalcredentials_available'] = 'Globalne poświadczenia API są skonfigurowane';
$string['globalcredentials_available_desc'] = 'Ta kategoria będzie używać globalnych poświadczeń API Credentium skonfigurowanych w Administracja witryny > Wtyczki > Wtyczki lokalne > Credentium.';
$string['globalcredentials_notavailable'] = 'Globalne poświadczenia API nie są skonfigurowane';
$string['globalcredentials_notavailable_desc'] = 'Globalne poświadczenia API nie zostały skonfigurowane. Musisz albo skonfigurować je w Administracja witryny > Wtyczki > Wtyczki lokalne > Credentium, albo wybrać "Użyj niestandardowych poświadczeń API" powyżej i skonfigurować poświadczenia specyficzne dla kategorii.';
$string['apicredentials'] = 'Poświadczenia API';
$string['apiurl_help'] = 'Adres URL punktu końcowego API Credentium dla tej kategorii (np. https://api.credentium.com lub https://tenant1.credentium.com).';
$string['apikey_help'] = 'Twój klucz API Credentium do uwierzytelniania. Ten klucz zostanie zaszyfrowany podczas zapisywania.';
$string['operations'] = 'Kontrole operacyjne';
$string['paused'] = 'Wstrzymaj wszystkie wydania';
$string['paused_help'] = 'Po wstrzymaniu nowe certyfikaty nie będą wydawane dla kursów używających konfiguracji tej kategorii. Istniejące oczekujące certyfikaty pozostaną oczekujące do czasu wznowienia.';
$string['ratelimit'] = 'Limit częstotliwości (certyfikatów na godzinę)';
$string['ratelimit_help'] = 'Maksymalna liczba certyfikatów, które mogą być wydane na godzinę dla wszystkich kursów używających konfiguracji tej kategorii. Pozostaw puste dla braku limitu.';

// Roles.
$string['credentiumcoursemanager'] = 'Menedżer kursów Credentium';
$string['credentiumcoursemanager_desc'] = 'Może konfigurować integrację Credentium dla kursów. Przypisz tę rolę użytkownikom na poziomie kursu lub kategorii, aby przyznać im uprawnienia do włączania i konfigurowania cyfrowych certyfikatów.';

// Capabilities.
$string['credentium:manage'] = 'Zarządzanie ustawieniami Credentium';
$string['credentium:managecourse'] = 'Zarządzanie ustawieniami Credentium kursu';
$string['credentium:managecategory'] = 'Zarządzanie ustawieniami Credentium kategorii';
$string['credentium:viewreports'] = 'Przeglądanie raportów Credentium';
$string['credentium:viewowncredentials'] = 'Przeglądanie własnych certyfikatów';

// Events.
$string['eventcredentialissued'] = 'Certyfikat wydany';
$string['eventcredentialfailed'] = 'Wydanie certyfikatu nie powiodło się';
$string['eventapierror'] = 'Błąd API Credentium';

// Report.
$string['report'] = 'Raport Credentium';
$string['issuancehistory'] = 'Historia wydawania certyfikatów';
$string['user'] = 'Użytkownik';
$string['course'] = 'Kurs';
$string['credentialid'] = 'ID certyfikatu';
$string['status'] = 'Status';
$string['issuedate'] = 'Data wydania';
$string['actions'] = 'Akcje';
$string['retry'] = 'Ponów';
$string['view'] = 'Wyświetl';
$string['export'] = 'Eksportuj';
$string['grade'] = 'Ocena';

// Statuses.
$string['status_pending'] = 'Oczekujące';
$string['status_issued'] = 'Wydane';
$string['status_failed'] = 'Nieudane';
$string['status_retrying'] = 'Ponawianie';

// Errors.
$string['error:apinotconfigured'] = 'API Credentium nie jest poprawnie skonfigurowane.';
$string['error:coursenotfound'] = 'Kurs nie został znaleziony.';
$string['error:invalidtemplate'] = 'Wybrano nieprawidłowy szablon certyfikatu.';
$string['error:issuancefailed'] = 'Nie udało się wydać certyfikatu: {$a}';
$string['error:nopermission'] = 'Nie masz uprawnień dostępu do tej strony.';
$string['error:notenabled'] = 'Integracja Credentium nie jest włączona.';
$string['error:categorymodedisabled'] = 'Tryb kategorii nie jest włączony. Włącz go najpierw w globalnych ustawieniach Credentium.';
$string['error:invalidapiurl'] = 'Podano nieprawidłowy URL API.';
$string['error:invalidratelimit'] = 'Limit częstotliwości musi być dodatnią liczbą całkowitą.';
$string['invalidapiurl'] = 'Podano nieprawidłowy URL API.';

// Tasks.
$string['task:issueCredentials'] = 'Wydaj oczekujące certyfikaty';
$string['task:retryFailedIssuances'] = 'Ponów nieudane wydania certyfikatów';
$string['task:syncTemplates'] = 'Synchronizuj szablony certyfikatów';
$string['task:processpending'] = 'Przetwarzaj oczekujące certyfikaty';
$string['task:cleanupoldissuances'] = 'Wyczyść stare rekordy wydanych certyfikatów (RODO)';

// Template selection.
$string['selecttemplate'] = 'Wybierz szablon...';
$string['notemplates'] = 'Brak dostępnych szablonów';
$string['refreshtemplates'] = 'Odśwież szablony';
$string['sendgrade'] = 'Wyślij ocenę z certyfikatem';
$string['sendgrade_help'] = 'Po włączeniu końcowa ocena studenta z kursu zostanie dołączona do certyfikatu. Jeśli agregacja ocen jest nadal w toku w momencie ukończenia kursu, system będzie ponawiać próbę, aby zapewnić dołączenie prawidłowej oceny.';
$string['issuanceinfo'] = 'Automatyczne wydawanie certyfikatów';
$string['issuanceinfo_desc'] = '<strong>Ważne:</strong> Gdy Credentium jest włączone dla tego kursu, mikrokwalifikacje będą automatycznie wydawane wszystkim studentom, którzy ukończą kurs. Upewnij się, że wybrałeś odpowiedni szablon certyfikatu przed włączeniem.';
$string['issuanceinfo_help'] = 'Certyfikaty są wydawane automatycznie po ukończeniu kursu. Możesz wybrać, czy dołączyć ocenę z kursu do certyfikatu, używając powyższej opcji.';
$string['templaterefreshed'] = 'Szablony odświeżone pomyślnie';

// Bulk operations.
$string['selectstudents'] = 'Wybierz studentów';
$string['issueselected'] = 'Wydaj wybranym studentom';
$string['bulkissuanceinitiated'] = 'Zainicjowano masowe wydanie certyfikatów dla {$a} studentów.';

// Notifications.
$string['notification:credentialissued'] = 'Twój certyfikat dla {$a} został pomyślnie wydany!';
$string['notification:credentialfailed'] = 'Wystąpił błąd podczas wydawania certyfikatu dla {$a}. Automatycznie ponowimy próbę.';

// Course completion settings.
$string['completionrequired'] = 'Wymagane ukończenie kursu';
$string['completionrequired_help'] = 'Studenci muszą ukończyć kurs przed otrzymaniem certyfikatów.';
$string['graderequired'] = 'Wymagana minimalna ocena';
$string['graderequired_help'] = 'Studenci muszą osiągnąć tę ocenę lub wyższą, aby otrzymać certyfikaty.';

// View credential.
$string['viewcredential'] = 'Wyświetl certyfikat';
$string['credentialdetails'] = 'Szczegóły certyfikatu';
$string['viewcredentialexternal'] = 'Wyświetl certyfikat w Credentium';
$string['retryscheduled'] = 'Zaplanowano ponowną próbę.';
$string['recordnotfound'] = 'Nie znaleziono rekordu.';
$string['processcredentials'] = 'Przetwarzaj certyfikaty';
$string['processingcredentials'] = 'Przetwarzanie oczekujących certyfikatów';
$string['nopendingcredentials'] = 'Brak oczekujących certyfikatów do przetworzenia.';
$string['processingcount'] = 'Przetwarzanie {$a} oczekujących certyfikatów...';
$string['backtoreport'] = 'Powrót do raportu';
$string['processpending'] = 'Przetwarzaj {$a} oczekujących certyfikatów';
$string['refresh'] = 'Odśwież';
$string['searchuser'] = 'Szukaj użytkownika po nazwie lub e-mailu';
$string['all'] = 'Wszystkie';
$string['search'] = 'Szukaj';
$string['filter'] = 'Filtruj';
$string['reset'] = 'Resetuj';
$string['downloadcsv'] = 'Pobierz jako CSV';
$string['downloadexcel'] = 'Pobierz jako Excel';
$string['summary'] = 'Podsumowanie';
$string['total'] = 'Razem';
$string['inprogress'] = 'W toku';
