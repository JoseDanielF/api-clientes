<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$apiKey = '07d9f44524cc0083d60f4eacd05ea0e6';
$city = 'Sao Paulo';
$dbHost = 'localhost';
$dbName = 'db_api';
$dbUser = 'root';
$dbPass = '';

$client = new Client();
$weatherData = null;

try {
    $response = $client->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
        'query' => [
            'q' => $city,
            'appid' => $apiKey,
            'units' => 'metric', 
            'lang' => 'en'      
        ]
    ]);

    if ($response->getStatusCode() == 200) {
        $body = $response->getBody()->getContents();
        $weatherData = json_decode($body, true); 
    }

} catch (RequestException $e) {
    echo "Erro na requisição à API de clima: " . $e->getMessage() . "\n";
    exit; 
}

if ($weatherData) {
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO weather (city, temperature, feels_like, humidity, description)
                VALUES (:city, :temperature, :feels_like, :humidity, :description)
                ON DUPLICATE KEY UPDATE
                temperature = VALUES(temperature),
                feels_like = VALUES(feels_like),
                humidity = VALUES(humidity),
                description = VALUES(description),
                updated_at = NOW()";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':city' => $weatherData['name'],
            ':temperature' => $weatherData['main']['temp'],
            ':feels_like' => $weatherData['main']['feels_like'],
            ':humidity' => $weatherData['main']['humidity'],
            ':description' => ucfirst($weatherData['weather'][0]['description'])
        ]);

        echo "Dados do clima inseridos/atualizados com sucesso para a cidade: " . $weatherData['name'] . "\n";
    } catch (PDOException $e) {
        echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n";
    }
}