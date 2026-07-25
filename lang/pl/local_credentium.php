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
$string['privacy:metadata'] = 'Wtyczka Integracja Credentium® przechowuje lokalnie informacje o wydanych poświadczeniach i przesyła dane użytkowników do zewnętrznej usługi Credentium® (płatne API strony trzeciej).';

// Local database storage.
$string['privacy:metadata:local_credentium_issuances'] = 'Informacje o poświadczeniach wydanych użytkownikom.';
$string['privacy:metadata:local_credentium_issuances:userid'] = 'ID użytkownika, który otrzymał poświadczenie.';
$string['privacy:metadata:local_credentium_issuances:courseid'] = 'ID kursu, dla którego wydano poświadczenie.';
$string['privacy:metadata:local_credentium_issuances:credentialid'] = 'Zewnętrzny ID poświadczenia w systemie Credentium.';
$string['privacy:metadata:local_credentium_issuances:status'] = 'Status wydania poświadczenia.';
$string['privacy:metadata:local_credentium_issuances:timecreated'] = 'Czas wydania poświadczenia.';

// External service - Credentium API (PAID commercial service).
$string['privacy:metadata:credentium_api'] = 'API Credentium® to płatna usługa strony trzeciej (https://credentium.com) służąca do wydawania cyfrowych poświadczeń. Dane użytkowników są przesyłane do tej zewnętrznej usługi.';
$string['privacy:metadata:credentium_api:email'] = 'Adres e-mail użytkownika jest wysyłany do Credentium® w celu identyfikacji odbiorcy poświadczenia.';
$string['privacy:metadata:credentium_api:firstname'] = 'Imię użytkownika jest wysyłane do Credentium® w celu personalizacji poświadczenia.';
$string['privacy:metadata:credentium_api:lastname'] = 'Nazwisko użytkownika jest wysyłane do Credentium® w celu personalizacji poświadczenia.';
$string['privacy:metadata:credentium_api:coursename'] = 'Nazwa kursu jest wysyłana do Credentium® w celu identyfikacji kursu, którego dotyczy poświadczenie.';
$string['privacy:metadata:credentium_api:grade'] = 'Ocena użytkownika z kursu (jeśli włączona) jest wysyłana do Credentium® w celu umieszczenia na poświadczeniu.';
$string['privacy:metadata:credentium_api:templateid'] = 'ID szablonu poświadczenia jest wysyłane do Credentium® w celu określenia, który projekt poświadczenia ma zostać użyty.';

// Settings.
$string['settings'] = 'Ustawienia Credentium';
$string['globalsettings'] = 'Ustawienia globalne Credentium';
$string['settings_desc'] = 'Skonfiguruj połączenie z API Credentium do wydawania mikropoświadczeń.';
$string['pluginversion'] = 'Wersja wtyczki';
$string['pluginversion_info'] = 'Wersja <strong>{$a->release}</strong> (kompilacja {$a->version})';
$string['enabled'] = 'Włącz integrację Credentium';
$string['enabled_desc'] = 'Włącz lub wyłącz integrację Credentium globalnie.';
$string['enabled_help'] = 'Po włączeniu wtyczka będzie automatycznie wydawać cyfrowe poświadczenia studentom po ukończeniu kursu (jeśli kurs jest również skonfigurowany indywidualnie).';
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
$string['testconnection_category_prefix'] = 'Kategoria: {$a}';
$string['testconnection_category_notfound'] = 'Nie znaleziono konfiguracji kategorii lub nie jest włączona.';
$string['testconnection_global_config'] = 'Konfiguracja globalna';
$string['testconnection_credentials_source'] = 'Źródło poświadczeń:';
$string['testconnection_apiurl_label'] = 'URL API:';
$string['testconnection_apikey_label'] = 'Klucz API:';
$string['testconnection_notconfigured'] = 'Nie skonfigurowano';
$string['testconnection_attempting'] = 'Próba pobrania szablonów z API...';
$string['testconnection_found_templates'] = 'Znaleziono {$a} szablon(ów) poświadczeń.';
$string['testconnection_templates_heading'] = 'Znalezione szablony:';
$string['testconnection_no_templates'] = 'Połączenie z API powiodło się, ale serwer zwrócił 0 szablonów. Sprawdź, czy Twój klucz API ma dostęp do szablonów.';
$string['testconnection_debug_heading'] = 'Szczegółowe informacje debugowania';
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
$string['dataretention_help'] = 'Jak długo przechowywać rekordy wydanych poświadczeń w bazie danych przed automatycznym usunięciem. Rekordy starsze niż ten okres będą trwale usunięte przez zaplanowane zadanie, które działa codziennie o 2:00 w nocy (czas serwera). Zapewnia to zgodność z RODO poprzez wdrażanie zasad minimalizacji danych. Domyślnie: 365 dni (1 rok). Dostępne jednostki: dni, tygodnie. Uwaga: Ponieważ czyszczenie działa codziennie, rzeczywiste przechowywanie może być do 24 godzin dłuższe niż skonfigurowane. Usuwanie dotyczy wszystkich rekordów wydanych poświadczeń niezależnie od statusu (wydane, nieudane lub oczekujące).';

// Course settings.
$string['coursesettings'] = 'Ustawienia Credentium';
$string['courseenabled'] = 'Włącz Credentium dla tego kursu';
$string['courseenabled_help'] = 'Po włączeniu studenci otrzymają poświadczenia Credentium po ukończeniu kursu.';
$string['credentialtemplate'] = 'Szablon poświadczenia';
$string['credentialtemplate_help'] = 'Wybierz szablon poświadczenia do użycia w tym kursie.';
$string['templaterequiresgrade'] = 'Ten szablon wymaga oceny';
$string['templaterequiresgrade_info'] = 'Ten szablon poświadczenia zawiera twierdzenie(-a) oceny uczenia się, dlatego ocena studenta z kursu zostanie umieszczona na mikropoświadczeniu. Wydanie poświadczenia nie powiedzie się, jeśli ocena nie będzie dostępna.';
$string['templatenograderequired'] = 'Ten szablon nie zawiera twierdzeń oceny';
$string['templatenograderequired_info'] = 'Ten szablon poświadczenia nie zawiera twierdzeń oceny uczenia się, więc ocena z kursu (nawet jeśli dostępna) nie zostanie przedstawiona na mikropoświadczeniu.';
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
$string['globalcredentials_available_desc'] = 'Ta kategoria będzie używać globalnych poświadczeń API Credentium skonfigurowanych w Administracja witryny > Wtyczki > Wtyczki lokalne > Integracja Credentium®.';
$string['globalcredentials_notavailable'] = 'Globalne poświadczenia API nie są skonfigurowane';
$string['globalcredentials_notavailable_desc'] = 'Globalne poświadczenia API nie zostały skonfigurowane. Musisz albo skonfigurować je w Administracja witryny > Wtyczki > Wtyczki lokalne > Integracja Credentium®, albo wybrać "Użyj niestandardowych poświadczeń API" powyżej i skonfigurować poświadczenia specyficzne dla kategorii.';
$string['categoryenabled'] = 'Włącz Credentium dla tej kategorii';
$string['categoryenabled_help'] = 'Po włączeniu kursy w tej kategorii mogą korzystać z tych poświadczeń API. Każdy kurs nadal musi zostać włączony indywidualnie w swoich ustawieniach.';
$string['apicredentials'] = 'Poświadczenia API';
$string['apiurl_help'] = 'Adres URL punktu końcowego API Credentium dla tej kategorii (np. https://api.credentium.com lub https://tenant1.credentium.com).';
$string['apikey_help'] = 'Twój klucz API Credentium do uwierzytelniania. Ten klucz zostanie zaszyfrowany podczas zapisywania.';
$string['operations'] = 'Kontrole operacyjne';
$string['paused'] = 'Wstrzymaj wszystkie wydania';
$string['paused_help'] = 'Po wstrzymaniu nowe poświadczenia nie będą wydawane dla kursów używających konfiguracji tej kategorii. Istniejące oczekujące poświadczenia pozostaną oczekujące do czasu wznowienia.';
$string['ratelimit'] = 'Limit częstotliwości (poświadczeń na godzinę)';
$string['ratelimit_help'] = 'Maksymalna liczba poświadczeń, które mogą być wydane na godzinę dla wszystkich kursów używających konfiguracji tej kategorii. Pozostaw puste dla braku limitu.';

// Roles.
$string['credentiumcoursemanager'] = 'Menedżer kursów Credentium';
$string['credentiumcoursemanager_desc'] = 'Może konfigurować integrację Credentium dla kursów. Przypisz tę rolę użytkownikom na poziomie kursu lub kategorii, aby przyznać im uprawnienia do włączania i konfigurowania cyfrowych poświadczeń.';

// Capabilities.
$string['credentium:manage'] = 'Zarządzanie ustawieniami Credentium';
$string['credentium:managecourse'] = 'Zarządzanie ustawieniami Credentium kursu';
$string['credentium:managecategory'] = 'Zarządzanie ustawieniami Credentium kategorii';
$string['credentium:viewreports'] = 'Przeglądanie raportów Credentium';
$string['credentium:viewowncredentials'] = 'Przeglądanie własnych poświadczeń';

// Events.
$string['eventcredentialissued'] = 'Poświadczenie wydane';
$string['eventcredentialfailed'] = 'Wydanie poświadczenia nie powiodło się';
$string['eventapierror'] = 'Błąd API Credentium';

// Report.
$string['report'] = 'Raport Credentium';
$string['issuancehistory'] = 'Historia wydawania poświadczeń';
$string['user'] = 'Użytkownik';
$string['course'] = 'Kurs';
$string['credentialid'] = 'ID poświadczenia';
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
$string['error:invalidtemplate'] = 'Wybrano nieprawidłowy szablon poświadczenia.';
$string['error:issuancefailed'] = 'Nie udało się wydać poświadczenia: {$a}';
$string['error:nopermission'] = 'Nie masz uprawnień dostępu do tej strony.';
$string['error:notenabled'] = 'Integracja Credentium nie jest włączona.';
$string['error:categorymodedisabled'] = 'Tryb kategorii nie jest włączony. Włącz go najpierw w globalnych ustawieniach Credentium.';
$string['error:invalidapiurl'] = 'Podano nieprawidłowy URL API.';
$string['error:invalidratelimit'] = 'Limit częstotliwości musi być dodatnią liczbą całkowitą.';
$string['error:template_multiple_assessments'] = 'Wybrany szablon zawiera wiele ocen uczenia się (Learning Assessments) i nie jest obsługiwany. Wybierz szablon z maksymalnie 1 oceną uczenia się.';
$string['invalidapiurl'] = 'Podano nieprawidłowy URL API.';

// Template warnings.
$string['template_unsupported_suffix'] = '(nieobsługiwany)';
$string['template_unsupported_warning'] = 'Ten szablon zawiera {$a} ocen uczenia się (Learning Assessments). Wtyczka obsługuje obecnie tylko szablony z maksymalnie 1 oceną uczenia się. Wybierz inny szablon.';

// Tasks.
$string['task:issueCredentials'] = 'Wydaj oczekujące poświadczenia';
$string['task:retryFailedIssuances'] = 'Ponów nieudane wydania poświadczeń';
$string['task:syncTemplates'] = 'Synchronizuj szablony poświadczeń';
$string['task:processpending'] = 'Przetwarzaj oczekujące poświadczenia';
$string['task:cleanupoldissuances'] = 'Wyczyść stare rekordy wydanych poświadczeń (RODO)';

// Template selection.
$string['selecttemplate'] = 'Wybierz szablon...';
$string['notemplates'] = 'Brak dostępnych szablonów';
$string['refreshtemplates'] = 'Odśwież szablony';
$string['sendgrade'] = 'Wyślij ocenę z poświadczeniem';
$string['sendgrade_help'] = 'Po włączeniu końcowa ocena studenta z kursu zostanie dołączona do poświadczenia. Jeśli agregacja ocen jest nadal w toku w momencie ukończenia kursu, system będzie ponawiać próbę, aby zapewnić dołączenie prawidłowej oceny.';
$string['issuanceinfo'] = 'Automatyczne wydawanie poświadczeń';
$string['issuanceinfo_desc'] = '<strong>Ważne:</strong> Gdy Credentium jest włączone dla tego kursu, mikropoświadczenia będą automatycznie wydawane wszystkim studentom, którzy ukończą kurs. Upewnij się, że wybrałeś odpowiedni szablon poświadczenia przed włączeniem.';
$string['issuanceinfo_help'] = 'Poświadczenia są wydawane automatycznie po ukończeniu kursu. Możesz wybrać, czy dołączyć ocenę z kursu do poświadczenia, używając powyższej opcji.';
$string['templaterefreshed'] = 'Szablony odświeżone pomyślnie';

// Bulk operations.
$string['selectstudents'] = 'Wybierz studentów';
$string['issueselected'] = 'Wydaj wybranym studentom';
$string['bulkissuanceinitiated'] = 'Zainicjowano masowe wydanie poświadczeń dla {$a} studentów.';

// Notifications.
$string['messageprovider:credentialissuance'] = 'Powiadomienia o cyfrowych poświadczeniach';
$string['notification:credentialissued_subject'] = 'Masz nowe cyfrowe poświadczenie';
$string['notification:credentialissued'] = 'Gratulujemy ukończenia kursu {$a}! Zleciliśmy wystawienie Twojego cyfrowego poświadczenia.

Poświadczenie jest zwykle gotowe w ciągu 15 minut. Otrzymasz osobne powiadomienie, gdy będzie gotowe do odbioru.';
$string['notification:credentialfailed_subject'] = 'Problem z Twoim cyfrowym poświadczeniem';
$string['notification:credentialfailed'] = 'Nie udało się zlecić wystawienia Twojego cyfrowego poświadczenia za kurs {$a}.

Skontaktuj się z prowadzącym kurs lub administratorem serwisu, aby sprawdzili, co poszło nie tak.';

// Course completion settings.
$string['completionrequired'] = 'Wymagane ukończenie kursu';
$string['completionrequired_help'] = 'Studenci muszą ukończyć kurs przed otrzymaniem poświadczeń.';
$string['graderequired'] = 'Wymagana minimalna ocena';
$string['graderequired_help'] = 'Studenci muszą osiągnąć tę ocenę lub wyższą, aby otrzymać poświadczenia.';

// View credential.
$string['viewcredential'] = 'Wyświetl poświadczenie';
$string['credentialdetails'] = 'Szczegóły poświadczenia';
$string['viewcredentialexternal'] = 'Wyświetl poświadczenie w Credentium';
$string['retryscheduled'] = 'Zaplanowano ponowną próbę.';
$string['recordnotfound'] = 'Nie znaleziono rekordu.';
$string['processcredentials'] = 'Przetwarzaj poświadczenia';
$string['processingcredentials'] = 'Przetwarzanie oczekujących poświadczeń';
$string['nopendingcredentials'] = 'Brak oczekujących poświadczeń do przetworzenia.';
$string['processingcount'] = 'Przetwarzanie {$a} oczekujących poświadczeń...';
$string['backtoreport'] = 'Powrót do raportu';
$string['processpending'] = 'Przetwarzaj {$a} oczekujących poświadczeń';
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

// Exception messages the API client raises (identifiers without a lang string
// would render as placeholders on error pages).
$string['apierror'] = 'Błąd API Credentium®: {$a}';
$string['apinotconfigured'] = 'API Credentium® nie jest skonfigurowane.';
$string['invalidjsonresponse'] = 'API Credentium® zwróciło nieprawidłową odpowiedź.';
$string['invalidresponse'] = 'API Credentium® zwróciło nieoczekiwaną odpowiedź.';
$string['issuancefailed'] = 'Nie udało się wystawić poświadczenia: {$a}';

// Claim feature (absorbed from the former Credentium® Claim plugin in v3.0.0).
$string['claimsettings_heading'] = 'Przypomnienia o odbiorze';
$string['claimsettings_desc'] = 'Sprawdzaj w Credentium®, czy wystawione poświadczenia zostały odebrane, i przypominaj uczestnikom — powiadomieniem, pozycją w menu użytkownika i banerem — o każdym poświadczeniu wciąż czekającym na odbiór.';
$string['claimenabled'] = 'Włącz sprawdzanie statusu odbioru i przypomnienia';
$string['claimenabled_help'] = 'Po włączeniu wtyczka okresowo sprawdza status odebrania wystawionych poświadczeń i wyświetla uczestnikom przypomnienie o odebraniu każdego poświadczenia, które nie zostało jeszcze odebrane.';
$string['maintenance_heading'] = 'Diagnostyka i retencja danych';
$string['syncinterval'] = 'Częstotliwość sprawdzania statusu';
$string['syncinterval_custom'] = 'Niestandardowa (zachowaj bieżący harmonogram)';
$string['syncinterval_help'] = 'Jak często zaplanowane zadanie pyta Credentium®, czy wystawione poświadczenia są gotowe do odbioru lub zostały odebrane. Krótsze odstępy oznaczają świeższe statusy kosztem większej liczby wywołań API i są przydatne podczas testów. Zmiana tej wartości przepisuje harmonogram zadania „Synchronizacja statusów odbioru poświadczeń Credentium”, który można też edytować w Serwer > Zaplanowane zadania.';
$string['showbanner'] = 'Pokaż baner przypomnienia';
$string['showbanner_help'] = 'Po włączeniu na górze każdej strony wyświetlany jest zamykalny baner dla uczestników mających nieodebrane poświadczenie. Po wyłączeniu uczestnicy nadal mogą odbierać poświadczenia ze strony „Moje poświadczenia”.';
$string['uninstalllegacyclaim'] = 'Osobna wtyczka Credentium® Claim (local_credentiumclaim) jest nadal zainstalowana. Od wersji 3.0.0 jej funkcjonalność jest wbudowana w tę wtyczkę, a dane i ustawienia zostały zmigrowane, więc została wyłączona — odinstaluj ją w Administracja serwisu > Wtyczki > Przegląd wtyczek.';

// Claim: learner-facing surfaces.
$string['banner_cta'] = 'Odbierz teraz';
$string['banner_dismiss'] = 'Odrzuć';
$string['banner_message'] = 'Credentium® wystawił Ci {$a} poświadczenie(-a) cyfrowe, których jeszcze nie odebrano.';
$string['banner_title'] = 'Masz nowe poświadczenie cyfrowe do odebrania';
$string['cachedef_claimable'] = 'Liczba nieodebranych poświadczeń Credentium® na użytkownika';
$string['claim'] = 'Odbierz';
$string['claim_alreadyclaimed'] = 'To poświadczenie zostało już przez Ciebie odebrane. Jest dostępne w Twoim Portfelu Credentium®.';
$string['claim_backtolist'] = 'Powrót do moich poświadczeń';
$string['claim_error'] = 'Nie udało się teraz otworzyć linku do odbioru. Spróbuj ponownie później.';
$string['claim_heading'] = 'Odbierz swoje poświadczenie';
$string['claim_notready'] = 'To poświadczenie jest jeszcze przygotowywane. Spróbuj ponownie za kilka minut.';
$string['col_action'] = 'Akcja';
$string['col_course'] = 'Kurs';
$string['col_status'] = 'Status';
$string['connection'] = 'Połączenie z API';
$string['credentium:claim'] = 'Odbieranie własnych poświadczeń Credentium®';
$string['message_ready_body'] = 'Credentium® wystawił Ci poświadczenie cyfrowe gotowe do odebrania. Otwórz „Moje poświadczenia”, aby zapisać je w swoim Portfelu Credentium®.';
$string['message_ready_body_course'] = 'Credentium® wystawił Ci poświadczenie cyfrowe za „{$a->course}” gotowe do odebrania. Otwórz „Moje poświadczenia”, aby zapisać je w swoim Portfelu Credentium®.';
$string['message_ready_linktext'] = 'Przejdź do moich poświadczeń';
$string['message_ready_small'] = 'Masz poświadczenie Credentium® gotowe do odebrania.';
$string['message_ready_subject'] = 'Masz poświadczenie do odebrania';
$string['messageprovider:credentialready'] = 'Poświadczenie cyfrowe jest gotowe do odebrania';
$string['mycredentials'] = 'Moje poświadczenia';
$string['mycredentials_empty'] = 'Nie wystawiono Ci jeszcze żadnych poświadczeń Credentium®.';
$string['mycredentials_heading'] = 'Moje poświadczenia Credentium®';
$string['mycredentials_intro'] = 'Te poświadczenia cyfrowe zostały Ci wystawione. Kliknij „Odbierz”, aby zapisać poświadczenie w swoim Portfelu Credentium®. Już odebrane pozostają na liście — „Zobacz w portfelu” otwiera każde z nich ponownie.';
$string['nav_mycredentials'] = 'Moje poświadczenia';
$string['nav_mycredentials_count'] = 'Moje poświadczenia ({$a})';
$string['viewinwallet'] = 'Zobacz w portfelu';

// Claim: status vocabulary (distinct from the issuance status_* strings above).
$string['claimstatus_claimed'] = 'Odebrane';
$string['claimstatus_failed'] = 'Niepowodzenie';
$string['claimstatus_issued'] = 'Gotowe do odbioru';
$string['claimstatus_processing'] = 'W przygotowaniu';
$string['claimstatus_unknown'] = 'Nieznany';

// Claim: status report and diagnostics.
$string['claimreport'] = 'Status odbioru Credentium®';
$string['report_checknow'] = 'Sprawdź status teraz';
$string['report_checknow_busy'] = 'Sprawdzanie statusu już trwa (uruchomione przez cron lub innego administratora). Spróbuj ponownie za chwilę.';
$string['report_checknow_failed'] = 'Nie udało się przeprowadzić sprawdzenia statusu. Włącz logowanie diagnostyczne, aby poznać przyczynę, lub sprawdź wynik ostatniego uruchomienia poniżej.';
$string['report_connection_missing'] = 'Nie skonfigurowano poświadczeń API, więc statusy odbioru nie mogą być sprawdzane.';
$string['report_connection_ok'] = 'Skonfigurowane ({$a})';
$string['report_count'] = 'Liczba';
$string['report_diagnostics'] = 'Diagnostyka';
$string['report_error_auth'] = 'Credentium® odrzucił klucz API. W ustawieniach wtyczki sprawdź, czy klucz jest nadal ważny i czy ma uprawnienie „credentials:read” — klucz, który może jedynie wystawiać poświadczenia, nie może odczytywać ich statusu.';
$string['report_error_busy'] = 'Credentium® poprosił wtyczkę o zwolnienie tempa lub przekroczył czas odpowiedzi. Wtyczka sama odczekuje i ponawia próbę, więc zwykle nie trzeba nic robić. Jeśli dzieje się to przy każdym uruchomieniu, zwiększ odstęp sprawdzania statusu w ustawieniach wtyczki.';
$string['report_error_client'] = 'Credentium® odrzucił samo żądanie. Zwykle oznacza to rozbieżność między wtyczką a API Credentium®: sprawdź, czy jest dostępna aktualizacja wtyczki, a kontaktując się ze wsparciem podaj szczegóły techniczne powyżej.';
$string['report_error_generic'] = 'Ostatnie sprawdzenie statusu nie powiodło się. Kontaktując się ze wsparciem, podaj szczegóły techniczne powyżej.';
$string['report_error_network'] = 'Nie udało się w ogóle połączyć z API Credentium®. Sprawdź, czy ten serwer może wykonywać wychodzące połączenia HTTPS pod skonfigurowany adres — najczęstszą przyczyną jest proxy, reguła zapory lub DNS.';
$string['report_error_pending'] = 'W tym uruchomieniu nie udało się sprawdzić {$a} śledzonych poświadczeń; zachowują one ostatnio znany status i zostaną sprawdzone ponownie automatycznie.';
$string['report_error_server'] = 'Credentium® przyjął żądanie, ale nie zdołał go zrealizować. To błąd po stronie Credentium®, a nie konfiguracji tej strony. Wtyczka już ponowiła próbę i spróbuje ponownie przy następnym zaplanowanym sprawdzeniu — statusy uzupełnią się same, gdy usługa wróci do działania. Jeśli błąd się powtarza, skontaktuj się ze wsparciem Credentium® i podaj szczegóły techniczne powyżej.';
$string['report_heading'] = 'Raport statusu odbioru Credentium®';
$string['report_intro'] = 'Przegląd śledzonych statusów odbioru poświadczeń wszystkich uczestników.';
$string['report_lastresult'] = 'Wynik ostatniego uruchomienia';
$string['report_lastrun'] = 'Ostatnie uruchomienie synchronizacji';
$string['report_lastsync'] = 'Ostatnie sprawdzenie poświadczenia';
$string['report_never'] = 'Nigdy';
$string['report_notified'] = 'Powiadomienia wysłane w ostatnim uruchomieniu';
$string['report_notifyfailed'] = 'Nie udało się wysłać {$a} powiadomień „gotowe do odbioru” w ostatnim uruchomieniu (uczestnik może być zawieszony lub kanał dostarczania mógł zawieść). Ci uczestnicy nadal zobaczą przypomnienie w menu użytkownika i banerze po zalogowaniu.';
$string['report_plugindisabled'] = 'Sprawdzanie statusu odbioru jest wyłączone, więc uczestnicy nie otrzymują przypomnień o nieodebranych poświadczeniach.';
$string['report_property'] = 'Właściwość';
$string['report_result_disabled'] = 'Pominięto: sprawdzanie statusu odbioru jest wyłączone.';
$string['report_result_error'] = 'Niepowodzenie: {$a}';
$string['report_result_notconfigured'] = 'Pominięto: nie skonfigurowano poświadczeń API.';
$string['report_result_ok'] = 'Zakończono: sprawdzono poświadczeń: {$a->polled}, zaktualizowano: {$a->updated}.';
$string['report_schedule_custom'] = 'Harmonogram niestandardowy (edytowany w Serwer > Zaplanowane zadania)';
$string['report_schedule_every'] = 'Co {$a}';
$string['report_status'] = 'Status';
$string['report_tasklastrun'] = 'Ostatnie uruchomienie zaplanowanego zadania';
$string['report_tasknextrun'] = 'Następne uruchomienie zaplanowanego zadania';
$string['report_total'] = 'Łącznie śledzonych poświadczeń';
$string['report_unmatched'] = 'Credentium® nie rozpoznał {$a} śledzonych poświadczeń. Zwykle oznacza to, że klucz API należy do innej organizacji niż ta, która je wystawiła.';
$string['report_unresolved'] = 'Pominięto {$a} śledzonych poświadczeń, ponieważ dla ich kursu nie ma żadnych poświadczeń API.';
$string['report_value'] = 'Wartość';
$string['task_syncclaimstatus'] = 'Synchronizacja statusów odbioru poświadczeń Credentium';
$string['testconnection_readscope_error'] = 'Nie udało się teraz zweryfikować dostępu do odczytu statusów. Może to być problem chwilowy, a nie konfiguracyjny. Szczegóły: {$a}';
$string['testconnection_readscope_fail'] = 'Klucz API został zaakceptowany, ale nie może odczytywać statusów poświadczeń. Funkcja odbioru wymaga zakresu credentials:read obok zakresów używanych do wystawiania — dodaj go do klucza w Credentium®. Szczegóły: {$a}';
$string['testconnection_readscope_ok'] = 'Klucz API może odczytywać statusy poświadczeń (zakres credentials:read jest obecny).';

// Claim: errors.
$string['error:claimapifailed'] = 'API Credentium® zwróciło błąd.';
$string['error:claimnotconfigured'] = 'Przypomnienia o odbiorze nie są włączone. Sprawdź ustawienia wtyczki Credentium®.';
$string['error:credentialnotfound'] = 'Nie znaleziono żądanego poświadczenia.';
$string['error:invalidjsonresponse'] = 'API Credentium® zwróciło nieprawidłową odpowiedź.';

// Claim: privacy.
$string['privacy:metadata:credentium_api:issuerequestid'] = 'Identyfikator żądania wystawienia (issueRequestId) Credentium® sprawdzanego lub odbieranego poświadczenia.';
$string['privacy:metadata:credentium_api:locale'] = 'Język, w którym ma być prezentowany proces odbioru.';
$string['privacy:metadata:local_credentium_claims'] = 'Informacje śledzące o wystawionych poświadczeniach Credentium® oraz o tym, czy uczestnik je odebrał.';
$string['privacy:metadata:local_credentium_claims:courseid'] = 'Identyfikator kursu, dla którego wystawiono poświadczenie.';
$string['privacy:metadata:local_credentium_claims:credentialid'] = 'Identyfikator samego poświadczenia w Credentium®, przechowywany po to, aby zgłoszenie do wsparcia dało się powiązać z rejestrami Credentium®.';
$string['privacy:metadata:local_credentium_claims:credentialkey'] = 'Identyfikator żądania wystawienia (issueRequestId) Credentium® dla poświadczenia.';
$string['privacy:metadata:local_credentium_claims:remotestatus'] = 'Ostatni znany status odbioru poświadczenia.';
$string['privacy:metadata:local_credentium_claims:timecreated'] = 'Czas utworzenia wiersza śledzenia.';
$string['privacy:metadata:local_credentium_claims:userid'] = 'Identyfikator użytkownika, do którego należy poświadczenie.';
