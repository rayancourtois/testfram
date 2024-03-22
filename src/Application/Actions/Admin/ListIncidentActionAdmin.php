<?php

namespace App\Application\Actions\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListIncidentActionAdmin
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $stmt = $this->pdo->query('SELECT * FROM incidents'); 
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
        $response->getBody()->write(json_encode($types));
        return $response->withHeader('Content-Type', 'application/json');
    }
}    
