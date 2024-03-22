<?php

namespace App\Application\Actions\Incident;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use PDO;

class DeclareIncidentAction
{
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $userId = $this->getUserIdFromToken($request);
    
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'code' => 'UNAUTHORIZED',
                'erreur' => 'Vous devez être connecté pour déclarer un incident.'
            ]));
            return $response->withStatus(401);
        }
    
        $titre = $data['titre'] ?? '';
        $description = $data['description'] ?? '';
        $latitude = $data['latitude'] ?? 0;
        $longitude = $data['longitude'] ?? 0;
        $date_event = $data['date_event'] ?? 0;
        $id_statut = $data['id_statut'] ?? 0;
        $idType = $data['id_type'] ?? 0;
        $photoData = $data['photo'] ?? '';

        $sqlPhoto = "INSERT INTO photo (value) VALUES (?)";

        $sql = "INSERT INTO incidents (titre, description, date_event, latitude, longitude, id_citoyen, id_type, id_photo, id_statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {

            $stmtPhoto = $this->pdo->prepare($sqlPhoto);
            $stmtPhoto->execute([$photoData]);
            $photoId = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$titre, $description, $date_event, $latitude, $longitude, $userId, $idType, $photoId, $id_statut]);
    
            $incidentId = $this->pdo->lastInsertId();
    
            $response->getBody()->write(json_encode(['idIncident' => $incidentId]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => $e->getMessage()]));
            return $response->withStatus(500);
        }
    }    

    private function getUserIdFromToken(Request $request) {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));

        if (!$token) {
            return null;
        }

        try {
            $decodedToken = JWT::decode($token, new Key('votre_cle_secrete', 'HS256'));
            return $decodedToken->sub;
        } catch (\Exception $e) {
            return null;
        }
    }
}
