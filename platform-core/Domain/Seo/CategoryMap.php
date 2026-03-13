<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

final class CategoryMap
{
    /**
     * @return array<string, string>
     */
    public static function templates(): array
    {
        return [
            'jak-zrobic' => 'Jak zrobic [X]',
            'jak-ustawic' => 'Jak ustawic [X]',
            'jak-naprawic' => 'Jak naprawic [X]',
            'jak-wymienic' => 'Jak wymienic [X]',
            'jak-zainstalowac' => 'Jak zainstalowac [X]',
            'jak-wyczyscic' => 'Jak wyczyscic [X]',
            'jak-skonfigurowac' => 'Jak skonfigurowac [X]',
            'jak-dziala' => 'Jak dziala [X]',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function postTypes(): array
    {
        return [
            'guide' => 'guide',
            'ranking' => 'ranking',
            'review' => 'review',
            'comparison' => 'comparison',
            'tool' => 'tool',
            'news' => 'news',
        ];
    }

    /**
     * @return array<string, array{label:string, subcategories:array<int, string>}>
     */
    public static function hubs(): array
    {
        return [
            'technologia' => ['label' => 'Technologia', 'subcategories' => ['routery', 'wifi', 'laptopy', 'komputery', 'systemy-operacyjne', 'oprogramowanie', 'konfiguracja-sprzetu', 'naprawa-sprzetu', 'instalacja-systemu', 'ustawienia-systemu']],
            'wordpress' => ['label' => 'WordPress', 'subcategories' => ['instalacja-wordpress', 'motywy-wordpress', 'wtyczki-wordpress', 'optymalizacja-wordpress', 'bezpieczenstwo-wordpress', 'backup-wordpress', 'migracja-wordpress', 'hosting-wordpress', 'woocommerce', 'page-buildery']],
            'seo' => ['label' => 'SEO', 'subcategories' => ['seo-podstawy', 'seo-techniczne', 'seo-on-page', 'seo-off-page', 'link-building', 'slowa-kluczowe', 'content-seo', 'local-seo', 'ecommerce-seo', 'narzedzia-seo']],
            'ai' => ['label' => 'AI', 'subcategories' => ['chatgpt', 'generowanie-tresci', 'prompty', 'automatyzacja-ai', 'chatboty', 'analiza-danych-ai', 'grafika-ai', 'wideo-ai', 'ai-w-biznesie', 'narzedzia-ai']],
            'marketing' => ['label' => 'Marketing', 'subcategories' => ['content-marketing', 'email-marketing', 'afiliacja', 'marketing-automation', 'strategia-marketingowa', 'reklamy-online', 'lead-generation', 'copywriting', 'landing-page', 'analityka-marketingowa']],
            'social-media' => ['label' => 'Social Media', 'subcategories' => ['instagram', 'facebook', 'tiktok', 'linkedin', 'youtube', 'pinterest', 'x-twitter', 'reels', 'short-video', 'reklamy-social-media']],
            'ecommerce' => ['label' => 'Ecommerce', 'subcategories' => ['woocommerce', 'shopify', 'prestashop', 'marketplace', 'platnosci-online', 'logistyka-ecommerce', 'optymalizacja-konwersji', 'koszyk-zakupowy', 'produkt-w-sklepie', 'automatyzacja-sprzedazy']],
            'internet' => ['label' => 'Internet', 'subcategories' => ['internet-domowy', 'routery', 'swiatlowod', 'dns', 'vpn', 'sieci-domowe', 'konfiguracja-modemu', 'predkosc-internetu', 'zasieg-wifi', 'bezpieczenstwo-internetu']],
            'komputery' => ['label' => 'Komputery', 'subcategories' => ['pc', 'laptopy', 'skladanie-komputera', 'upgrade-komputera', 'dyski-ssd', 'ram', 'bios', 'sterowniki', 'chlodzenie', 'naprawa-komputera']],
            'programowanie' => ['label' => 'Programowanie', 'subcategories' => ['html', 'css', 'javascript', 'php', 'python', 'frontend', 'backend', 'debugowanie', 'api', 'frameworki']],
            'smartfony' => ['label' => 'Smartfony', 'subcategories' => ['android', 'iphone', 'ustawienia-telefonu', 'bateria', 'aparat', 'aktualizacje', 'kopia-zapasowa', 'reset-telefonu', 'pamiec-telefonu', 'bezpieczenstwo-mobilne']],
            'aplikacje' => ['label' => 'Aplikacje', 'subcategories' => ['aplikacje-mobilne', 'aplikacje-desktopowe', 'konfiguracja-aplikacji', 'aktualizacja-aplikacji', 'synchronizacja', 'produktywnosc-apps', 'komunikatory', 'aplikacje-ai', 'aplikacje-biznesowe', 'troubleshooting-aplikacji']],
            'bezpieczenstwo-it' => ['label' => 'Bezpieczenstwo IT', 'subcategories' => ['antywirus', 'firewall', 'backup', 'ransomware', 'hasla', '2fa', 'ochrona-danych', 'cyberbezpieczenstwo', 'szyfrowanie', 'monitoring-bezpieczenstwa']],
            'dom' => ['label' => 'Dom', 'subcategories' => ['organizacja-domu', 'sprzatanie', 'wyposazenie-domu', 'smart-home', 'oszczedzanie-energii', 'pralnia', 'kuchnia', 'lazienka', 'przechowywanie', 'domowe-naprawy']],
            'remont' => ['label' => 'Remont', 'subcategories' => ['malowanie-scian', 'gladzie', 'podlogi', 'plytki', 'montaz-mebli', 'elektryka', 'hydraulika', 'drzwi', 'okna', 'wykonczenie-wnetrz']],
            'ogrod' => ['label' => 'Ogrod', 'subcategories' => ['trawnik', 'rosliny', 'warzywnik', 'podlewanie', 'narzedzia-ogrodowe', 'meble-ogrodowe', 'projekt-ogrodu', 'przycinanie', 'nawozenie', 'ochrona-roslin']],
            'motoryzacja' => ['label' => 'Motoryzacja', 'subcategories' => ['olej-silnikowy', 'opony', 'akumulator', 'hamulce', 'detailing', 'ubezpieczenie-auta', 'zakup-auta', 'eksploatacja-auta', 'elektryka-samochodowa', 'diagnostyka-auta']],
            'zdrowie' => ['label' => 'Zdrowie', 'subcategories' => ['zdrowie-ogolne', 'odpornosc', 'sen', 'profilaktyka', 'badania', 'zdrowie-psychiczne', 'ergonomia', 'regeneracja', 'nawyki-zdrowotne', 'wellbeing']],
            'fitness' => ['label' => 'Fitness', 'subcategories' => ['trening-w-domu', 'silownia', 'bieganie', 'mobilnosc', 'stretching', 'plan-treningowy', 'spalanie-tluszczu', 'masa-miesniowa', 'cardio', 'cwiczenia-dla-poczatkujacych']],
            'dieta' => ['label' => 'Dieta', 'subcategories' => ['odchudzanie', 'dieta-keto', 'dieta-srodziemnomorska', 'meal-prep', 'kalorie', 'suplementy', 'zdrowe-przepisy', 'dieta-sportowa', 'nawadnianie', 'nawyki-zywieniowe']],
            'edukacja' => ['label' => 'Edukacja', 'subcategories' => ['nauka-jezykow', 'techniki-nauki', 'egzaminy', 'studia', 'kursy-online', 'notatki', 'koncentracja', 'plan-nauki', 'nauka-z-ai', 'edukacja-cyfrowa']],
            'biznes' => ['label' => 'Biznes', 'subcategories' => ['zakladanie-firmy', 'model-biznesowy', 'sprzedaz-b2b', 'zarzadzanie-zespolem', 'operacje-firmy', 'obsluga-klienta', 'skalowanie-biznesu', 'procesy', 'freelancing', 'startup']],
            'finanse' => ['label' => 'Finanse', 'subcategories' => ['oszczedzanie', 'inwestowanie', 'kredyty', 'budzet-domowy', 'konto-bankowe', 'podatki', 'ksiegowosc', 'ubezpieczenia', 'poduszka-finansowa', 'finanse-firmy']],
            'produktywnosc' => ['label' => 'Produktywnosc', 'subcategories' => ['zarzadzanie-czasem', 'planowanie', 'organizacja-pracy', 'workflow', 'automatyzacja', 'notatki', 'deep-work', 'narzedzia-produktywnosci', 'focus', 'rutyny']],
            'podroze' => ['label' => 'Podroze', 'subcategories' => ['planowanie-podrozy', 'tanie-loty', 'noclegi', 'pakowanie', 'city-break', 'bezpieczenstwo-podrozy', 'podroze-autem', 'podroze-z-dziecmi', 'dokumenty', 'budzet-podrozy']],
            'lifestyle' => ['label' => 'Lifestyle', 'subcategories' => ['rozwoj-osobisty', 'hobby', 'relacje', 'organizacja-zycia', 'minimalizm', 'wellbeing', 'work-life-balance', 'codzienne-nawyki', 'styl-zycia', 'self-care']],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function topicBank(): array
    {
        return [
            'technologia' => ['router wifi', 'router tp link', 'router asus', 'router mesh', 'router swiatlowodowy', 'komputer', 'laptop', 'windows', 'linux', 'macbook', 'drukarka', 'monitor', 'klawiatura', 'myszka komputerowa', 'kamera internetowa', 'mikrofon', 'dysk ssd', 'dysk hdd', 'bios', 'sterowniki', 'router gamingowy', 'router 5ghz', 'router 6ghz', 'router openwrt', 'router domowy'],
            'wordpress' => ['strona wordpress', 'blog wordpress', 'hosting wordpress', 'motyw wordpress', 'wtyczki wordpress', 'woocommerce', 'backup wordpress', 'seo wordpress', 'cache wordpress', 'ssl wordpress', 'migracja wordpress', 'aktualizacja wordpress', 'formularz kontaktowy wordpress', 'strona firmowa wordpress', 'landing page wordpress', 'elementor wordpress', 'wordpress security', 'wordpress speed', 'wordpress menu', 'wordpress widgets', 'wordpress child theme', 'wordpress kopia zapasowa', 'wordpress certyfikat ssl', 'wordpress redirect', 'wordpress breadcrumbs'],
            'seo' => ['audyt seo', 'slowa kluczowe', 'meta tagi', 'link building', 'google search console', 'sitemap', 'robots.txt', 'seo artykulu', 'seo techniczne', 'seo lokalne', 'canonical url', 'schema faq', 'schema article', 'google analytics seo', 'seo on page', 'seo off page', 'optymalizacja title', 'optymalizacja description', 'linkowanie wewnetrzne', 'szybkosc strony seo', 'core web vitals', 'seo wordpress', 'seo sklepu internetowego', 'pozycjonowanie lokalne', 'content seo'],
            'ai' => ['chatgpt', 'midjourney', 'dall-e', 'ai chatbot', 'generowanie tekstu ai', 'generowanie obrazow ai', 'automatyzacja ai', 'ai marketing', 'ai copywriting', 'ai grafika', 'prompt engineering', 'ai do seo', 'ai do social media', 'ai do sklepu internetowego', 'ai do obslugi klienta', 'ai workflow', 'ai video', 'ai research', 'ai email', 'ai produktywnosc', 'ai content plan', 'ai reklamy', 'ai raporty', 'ai analizator danych', 'ai voice'],
            'marketing' => ['kampania marketingowa', 'facebook ads', 'google ads', 'newsletter', 'strategia marketingowa', 'marketing automation', 'content marketing', 'copywriting', 'reklama online', 'lead generation', 'landing page', 'remarketing', 'lejki sprzedazowe', 'marketing b2b', 'marketing b2c', 'email funnel', 'branding', 'plan marketingowy', 'kampania display', 'kampania youtube', 'kampania meta ads', 'mierzenie konwersji', 'pixel facebook', 'google tag manager', 'kampania lead ads'],
            'social-media' => ['instagram konto', 'instagram reels', 'instagram reklamy', 'tiktok konto', 'tiktok viral', 'facebook fanpage', 'linkedin profil', 'youtube kanal', 'youtube monetyzacja', 'youtube seo', 'instagram bio', 'instagram stories', 'instagram hashtag', 'facebook reklamy', 'facebook grupa', 'linkedin ads', 'linkedin company page', 'youtube miniatury', 'youtube shorts', 'youtube watch time', 'tiktok ads', 'tiktok live', 'pinterest konto', 'social media plan', 'social media content'],
            'ecommerce' => ['sklep internetowy', 'woocommerce sklep', 'shopify sklep', 'dropshipping', 'opis produktu', 'platnosci online', 'paypal konto', 'stripe platnosci', 'koszyk zakupowy', 'marketing ecommerce', 'checkout ecommerce', 'porzucony koszyk', 'produkt cyfrowy', 'wysylka w sklepie', 'regulamin sklepu', 'zwroty ecommerce', 'integracja platnosci', 'sku produktu', 'karta produktu', 'seo ecommerce', 'automatyzacja sklepu', 'upsell ecommerce', 'cross sell ecommerce', 'marketplace allegro', 'shopify payments'],
            'internet' => ['wifi', 'dns', 'vpn', 'hotspot', 'router konfiguracja', 'internet mobilny', 'internet swiatlowodowy', 'szybkosc internetu', 'haslo wifi', 'siec domowa', 'ustawienia dns', 'wifi 2.4 ghz', 'wifi 5 ghz', 'restart routera', 'modem internetowy', 'mesh wifi', 'vpn na telefonie', 'vpn na komputerze', 'publiczne ip', 'prywatne ip', 'port forwarding', 'serwer dns', 'guest wifi', 'internet awaria', 'test predkosci internetu'],
            'komputery' => ['instalacja windows', 'instalacja linux', 'naprawa komputera', 'czyszczenie komputera', 'backup komputera', 'aktualizacja systemu', 'antywirus', 'firewall', 'przyspieszenie komputera', 'formatowanie dysku', 'reset windows', 'bios update', 'sterowniki windows', 'odzyskiwanie danych', 'partycje dysku', 'czyszczenie laptopa', 'temperatury komputera', 'wymiana dysku ssd', 'wymiana ram', 'konfiguracja bios', 'tryb awaryjny windows', 'start systemu', 'blue screen', 'kopiowanie systemu', 'monitoring podzespolow'],
            'dom' => ['zarowka', 'gniazdko elektryczne', 'kran', 'pralka', 'lodowka', 'piekarnik', 'zmywarka', 'drzwi', 'okno', 'polka scienna', 'wymiana zamka', 'cieknacy kran', 'montaz lampy', 'gniazdko usb', 'malowanie scian', 'uszczelnianie okna', 'regulacja drzwi', 'czyszczenie piekarnika', 'czyszczenie pralki', 'czyszczenie zmywarki', 'montaz polki', 'wymiana zarowki led', 'smart home dom', 'domowy bezpiecznik', 'syfon umywalki'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allTopics(): array
    {
        $topics = [];

        foreach (self::topicBank() as $items) {
            foreach ($items as $topic) {
                $topic = trim((string) $topic);

                if ($topic === '') {
                    continue;
                }

                $topics[] = $topic;
            }
        }

        $topics = array_values(array_unique($topics));
        sort($topics);

        return $topics;
    }

    /**
     * @return array<int, string>
     */
    public static function topicsForHub(string $hub): array
    {
        $hub = sanitize_key($hub);
        $bank = self::topicBank();

        if ($hub === '' || $hub === 'all' || ! isset($bank[$hub])) {
            return self::allTopics();
        }

        return array_values(array_unique(array_map('strval', $bank[$hub])));
    }

    /**
     * @return array<string, string>
     */
    public static function hubOptions(): array
    {
        $options = ['all' => 'Wszystkie huby'];

        foreach (self::hubs() as $hubKey => $hub) {
            $options[$hubKey] = (string) $hub['label'];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(): array
    {
        $hubs = self::hubs();
        $subcategoryCount = 0;
        $topicBank = self::topicBank();
        $baseTopicCount = 0;

        foreach ($hubs as $hub) {
            $subcategoryCount += count($hub['subcategories']);
        }

        foreach ($topicBank as $topics) {
            $baseTopicCount += count($topics);
        }

        $templateCount = count(self::templates());

        return [
            'hub_count' => count($hubs),
            'subcategory_count' => $subcategoryCount,
            'template_count' => $templateCount,
            'base_topic_count' => $baseTopicCount,
            'current_projected_articles' => $baseTopicCount * $templateCount,
            'recommended_topic_target' => 250,
            'recommended_projected_articles' => 250 * $templateCount,
            'scale_topic_target' => 6000,
            'scale_projected_articles' => 6000 * $templateCount,
            'daily_publication_target' => 10,
            'monthly_publication_target' => 300,
            'yearly_publication_target' => 3600,
            'topics_per_subcategory' => 25,
            'projected_guides' => $subcategoryCount * 25 * $templateCount,
        ];
    }
}