<?php
/**
 * Serviço de Localização Avançado
 * Obtém endereço completo através de coordenadas GPS e IP
 */

class LocationService {
    
    /**
     * Obtém localização completa por coordenadas GPS
     */
    public static function getAddressByCoordinates($latitude, $longitude) {
        $results = [];
        
        // Tenta múltiplas APIs para maior precisão
        $apis = [
            'nominatim' => self::getNominatimAddress($latitude, $longitude),
            'opencage' => self::getOpenCageAddress($latitude, $longitude),
            'positionstack' => self::getPositionStackAddress($latitude, $longitude)
        ];
        
        foreach ($apis as $provider => $result) {
            if ($result && !isset($result['error'])) {
                $results[$provider] = $result;
            }
        }
        
        // Retorna o melhor resultado disponível
        return self::getBestLocationResult($results);
    }
    
    /**
     * API Nominatim (OpenStreetMap) - Gratuita
     */
    private static function getNominatimAddress($lat, $lon) {
        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&zoom=18&addressdetails=1";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'LocationService/1.0 (contact@example.com)'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['error' => 'Falha na requisição Nominatim'];
            }
            
            $data = json_decode($response, true);
            
            if (!$data || isset($data['error'])) {
                return ['error' => 'Dados inválidos do Nominatim'];
            }
            
            $address = $data['address'] ?? [];
            
            return [
                'provider' => 'Nominatim (OpenStreetMap)',
                'formatted_address' => $data['display_name'] ?? 'N/A',
                'street_number' => $address['house_number'] ?? '',
                'street_name' => $address['road'] ?? $address['pedestrian'] ?? '',
                'neighborhood' => $address['neighbourhood'] ?? $address['suburb'] ?? '',
                'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? '',
                'state' => $address['state'] ?? '',
                'postal_code' => $address['postcode'] ?? '',
                'country' => $address['country'] ?? '',
                'country_code' => $address['country_code'] ?? '',
                'coordinates' => [
                    'lat' => $data['lat'],
                    'lon' => $data['lon']
                ],
                'accuracy' => 'high',
                'raw_data' => $data
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Exceção Nominatim: ' . $e->getMessage()];
        }
    }
    
    /**
     * API OpenCage - Gratuita com limite
     */
    private static function getOpenCageAddress($lat, $lon) {
        try {
            // Chave gratuita da OpenCage (substitua por uma válida)
            $apiKey = 'YOUR_OPENCAGE_API_KEY'; // Obtenha em https://opencagedata.com/
            
            if ($apiKey === 'YOUR_OPENCAGE_API_KEY') {
                return ['error' => 'Chave API OpenCage não configurada'];
            }
            
            $url = "https://api.opencagedata.com/geocode/v1/json?q={$lat}+{$lon}&key={$apiKey}&language=pt&pretty=1";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'LocationService/1.0'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['error' => 'Falha na requisição OpenCage'];
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['results'][0])) {
                return ['error' => 'Dados inválidos do OpenCage'];
            }
            
            $result = $data['results'][0];
            $components = $result['components'];
            
            return [
                'provider' => 'OpenCage',
                'formatted_address' => $result['formatted'],
                'street_number' => $components['house_number'] ?? '',
                'street_name' => $components['road'] ?? '',
                'neighborhood' => $components['neighbourhood'] ?? $components['suburb'] ?? '',
                'city' => $components['city'] ?? $components['town'] ?? $components['village'] ?? '',
                'state' => $components['state'] ?? '',
                'postal_code' => $components['postcode'] ?? '',
                'country' => $components['country'] ?? '',
                'country_code' => $components['country_code'] ?? '',
                'coordinates' => $result['geometry'],
                'accuracy' => 'high',
                'confidence' => $result['confidence'] ?? 0,
                'raw_data' => $result
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Exceção OpenCage: ' . $e->getMessage()];
        }
    }
    
    /**
     * API PositionStack - Gratuita com limite
     */
    private static function getPositionStackAddress($lat, $lon) {
        try {
            // Chave gratuita da PositionStack (substitua por uma válida)
            $apiKey = 'YOUR_POSITIONSTACK_API_KEY'; // Obtenha em https://positionstack.com/
            
            if ($apiKey === 'YOUR_POSITIONSTACK_API_KEY') {
                return ['error' => 'Chave API PositionStack não configurada'];
            }
            
            $url = "http://api.positionstack.com/v1/reverse?access_key={$apiKey}&query={$lat},{$lon}&limit=1";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'LocationService/1.0'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['error' => 'Falha na requisição PositionStack'];
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['data'][0])) {
                return ['error' => 'Dados inválidos do PositionStack'];
            }
            
            $result = $data['data'][0];
            
            return [
                'provider' => 'PositionStack',
                'formatted_address' => $result['label'] ?? 'N/A',
                'street_number' => $result['number'] ?? '',
                'street_name' => $result['street'] ?? '',
                'neighborhood' => $result['neighbourhood'] ?? '',
                'city' => $result['locality'] ?? '',
                'state' => $result['region'] ?? '',
                'postal_code' => $result['postal_code'] ?? '',
                'country' => $result['country'] ?? '',
                'country_code' => $result['country_code'] ?? '',
                'coordinates' => [
                    'lat' => $result['latitude'],
                    'lon' => $result['longitude']
                ],
                'accuracy' => 'medium',
                'confidence' => $result['confidence'] ?? 0,
                'raw_data' => $result
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Exceção PositionStack: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtém endereço por CEP (Brasil)
     */
    public static function getAddressByPostalCode($cep) {
        try {
            // Remove caracteres não numéricos
            $cep = preg_replace('/[^0-9]/', '', $cep);
            
            if (strlen($cep) !== 8) {
                return ['error' => 'CEP inválido'];
            }
            
            // API ViaCEP (gratuita)
            $url = "https://viacep.com.br/ws/{$cep}/json/";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'LocationService/1.0'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['error' => 'Falha na requisição ViaCEP'];
            }
            
            $data = json_decode($response, true);
            
            if (!$data || isset($data['erro'])) {
                return ['error' => 'CEP não encontrado'];
            }
            
            return [
                'provider' => 'ViaCEP',
                'postal_code' => $data['cep'],
                'street_name' => $data['logradouro'],
                'neighborhood' => $data['bairro'],
                'city' => $data['localidade'],
                'state' => $data['uf'],
                'state_name' => self::getStateName($data['uf']),
                'country' => 'Brasil',
                'country_code' => 'BR',
                'ibge_code' => $data['ibge'] ?? '',
                'gia_code' => $data['gia'] ?? '',
                'ddd' => $data['ddd'] ?? '',
                'siafi' => $data['siafi'] ?? '',
                'raw_data' => $data
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Exceção ViaCEP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtém o IP público real do usuário
     */
    public static function getRealPublicIP() {
        // Primeiro tenta obter do cabeçalho HTTP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Valida se é um IP público válido
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Se não conseguiu pelo cabeçalho, usa APIs externas
        $externalAPIs = [
            'https://api.ipify.org',
            'https://icanhazip.com',
            'https://ipecho.net/plain',
            'https://myexternalip.com/raw',
            'https://api.myip.com'
        ];
        
        foreach ($externalAPIs as $api) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 5,
                        'user_agent' => 'LocationService/1.0'
                    ]
                ]);
                
                $ip = trim(file_get_contents($api, false, $context));
                
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            } catch (Exception $e) {
                continue; // Tenta próxima API
            }
        }
        
        return null;
    }
    
    /**
     * Obtém localização por IP (fallback)
     */
    public static function getLocationByIP($ip = null) {
        if (!$ip) {
            $ip = self::getRealPublicIP();
        }
        
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return ['error' => 'IP público não encontrado'];
        }
        
        try {
            // API ip-api.com (gratuita)
            $url = "http://ip-api.com/json/{$ip}?fields=status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,offset,currency,isp,org,as,asname,reverse,mobile,proxy,hosting,query";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'LocationService/1.0'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['error' => 'Falha na requisição de geolocalização por IP'];
            }
            
            $data = json_decode($response, true);
            
            if (!$data || $data['status'] !== 'success') {
                return ['error' => $data['message'] ?? 'Erro na geolocalização por IP'];
            }
            
            return [
                'provider' => 'ip-api.com',
                'ip' => $data['query'],
                'city' => $data['city'],
                'state' => $data['regionName'],
                'state_code' => $data['region'],
                'country' => $data['country'],
                'country_code' => $data['countryCode'],
                'postal_code' => $data['zip'],
                'coordinates' => [
                    'lat' => $data['lat'],
                    'lon' => $data['lon']
                ],
                'timezone' => $data['timezone'],
                'isp' => $data['isp'],
                'organization' => $data['org'],
                'accuracy' => 'low',
                'raw_data' => $data
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Exceção na geolocalização por IP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Seleciona o melhor resultado de localização
     */
    private static function getBestLocationResult($results) {
        if (empty($results)) {
            return ['error' => 'Nenhum resultado de localização disponível'];
        }
        
        // Prioridade: OpenCage > Nominatim > PositionStack
        $priority = ['opencage', 'nominatim', 'positionstack'];
        
        foreach ($priority as $provider) {
            if (isset($results[$provider])) {
                $results[$provider]['selected_provider'] = $provider;
                $results[$provider]['available_providers'] = array_keys($results);
                return $results[$provider];
            }
        }
        
        // Retorna o primeiro disponível
        $firstResult = reset($results);
        $firstResult['selected_provider'] = key($results);
        $firstResult['available_providers'] = array_keys($results);
        
        return $firstResult;
    }
    
    /**
     * Converte código do estado para nome completo
     */
    private static function getStateName($uf) {
        $states = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];
        
        return $states[$uf] ?? $uf;
    }
    
    /**
     * Formata endereço completo para exibição
     */
    public static function formatAddress($locationData) {
        if (isset($locationData['error'])) {
            return "Erro: {$locationData['error']}";
        }
        
        $parts = [];
        
        // Número e rua
        if (!empty($locationData['street_number']) && !empty($locationData['street_name'])) {
            $parts[] = $locationData['street_name'] . ', ' . $locationData['street_number'];
        } elseif (!empty($locationData['street_name'])) {
            $parts[] = $locationData['street_name'];
        }
        
        // Bairro
        if (!empty($locationData['neighborhood'])) {
            $parts[] = $locationData['neighborhood'];
        }
        
        // Cidade
        if (!empty($locationData['city'])) {
            $parts[] = $locationData['city'];
        }
        
        // Estado
        if (!empty($locationData['state'])) {
            $parts[] = $locationData['state'];
        }
        
        // CEP
        if (!empty($locationData['postal_code'])) {
            $parts[] = 'CEP: ' . $locationData['postal_code'];
        }
        
        // País
        if (!empty($locationData['country'])) {
            $parts[] = $locationData['country'];
        }
        
        return implode(' - ', array_filter($parts));
    }
    
    /**
     * Obtém localização completa (GPS + IP como fallback)
     */
    public static function getCompleteLocation($gpsLat = null, $gpsLon = null, $ip = null) {
        $result = [
            'gps_location' => null,
            'ip_location' => null,
            'primary_location' => null,
            'formatted_address' => 'Localização não disponível'
        ];
        
        // Tenta GPS primeiro (mais preciso)
        if ($gpsLat && $gpsLon) {
            $gpsResult = self::getAddressByCoordinates($gpsLat, $gpsLon);
            if (!isset($gpsResult['error'])) {
                $result['gps_location'] = $gpsResult;
                $result['primary_location'] = $gpsResult;
                $result['formatted_address'] = self::formatAddress($gpsResult);
            }
        }
        
        // Fallback para IP
        if (!$result['primary_location']) {
            $ipResult = self::getLocationByIP($ip);
            if (!isset($ipResult['error'])) {
                $result['ip_location'] = $ipResult;
                $result['primary_location'] = $ipResult;
                $result['formatted_address'] = self::formatAddress($ipResult);
            }
        }
        
        return $result;
    }
}

?>