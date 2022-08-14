<?php

$response = [
    'status' => 'error',
    'data' => null
];
$rawBody = file_get_contents('php://input', 'r');
$body = json_decode($rawBody, true);

$db = new PDO('mysql:host=localhost;dbname=sekolah','root','');

function getNis() {
    if (!strpos($_SERVER['REQUEST_URI'], '?'))return false;

    $path = explode('?', $_SERVER['REQUEST_URI'])[1];
    $segment = explode('/', $path);
    return (int) $segment[1];
}

switch (strtolower($_SERVER['REQUEST_METHOD'])) {
    case 'post':
        // eksekusi insert data ke database
        try {
            if (!isset($body['nama'], $body['id_jurusan'], $body['id_walikelas'])) {
                throw new InvalidArgumentException('Invalid form');
            }
            $stmt =$db->prepare('INSERT INTO siswa (nis, nama, id_jurusan, id_walikelas) VALUES (:nis, :nama, :id_j, :id_w)');
            
            $stmt->execute([
                ':nis' => $body['nis'],
                ':nama' => $body['nama'],
                ':id_j' => $body['id_jurusan'],
                ':id_w' => $body['id_walikelas']
            ]);

            http_response_code(201);
            $response['status'] = 'success';
            $response['data'] = [];
                        
        } catch (Throwable $error) {
            if ($error instanceof InvalidArgumentException) {
                http_response_code(400);
            } else {
                http_response_code(500);
            }
            $response['error'] = $error->getMessage();
        }
        break;

       

    case 'get':
        $nis = getNis();

        if ($nis) {
            $stmt = $db->prepare('SELECT * FROM siswa WHERE nis = :nis');
            $stmt->execute([':nis' => $nis]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->query('SELECT s.nis, s.nama, j.nama nama_jurusan, w.nama nama_walikelas
                                FROM siswa s
                                JOIN jurusan j on j.id = s.id_jurusan
                                JOIN walikelas w on w.id = s.id_walikelas');
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if(!empty($data)) {

            $response['status'] = 'success';
            $response['data'] = $data;

            // foreach ($data as $i => $row) {
            //     $response['data'][$i] = [
            //         'nis' => $row['nis'],
            //         'nama' => $row['nama'],
            //         'nama_jurusan' => $row['nama_jurusan'],
            //         'nama_walikelas' => $row['nama_walikelas']
            //     ];
            // }
        } else {
            http_response_code(404);
        }    
        break;

    default:
        http_response_code(503);
    break;

    // .....................................................................

    case 'put':
        $nis = getNis();

        if(!$nis) {
            http_response_code(404);
            break;
        } 

        if (!isset($body['nama'], $body['id_jurusan'], $body['id_walikelas'])) {
            throw new InvalidArgumentException("Invalid Form");
        }

        $stmt = $db->prepare('SELECT * FROM siswa WHERE nis = :nis');
        $stmt->execute([':nis' => $nis]);

        if (!$stmt->rowCount()) {
            http_response_code(404);
            break;
        }

        // eksekusi update
        $stmt = $db->prepare('UPDATE siswa SET nama = :nama, id_jurusan = :id_j, id_walikelas = :id_w WHERE nis = :nis');

        $stmt->execute([
            ':nis' => $nis,
            ':nama' => $body['nama'],
            ':id_j' => $body['id_jurusan'],
            ':id_w' => $body['id_walikelas']
        ]);
    break;    

    // ........................................................................................    

    case 'delete':
        $nis = getNis();

        if(!$nis) {
            http_response_code(404);
            break;
        } 

        $stmt = $db->prepare('SELECT * FROM siswa WHERE nis = :nis');
        $stmt->bindParam(':nis', $nis, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->rowCount()) {
            http_response_code(404);
            break;
        }

        $stmt = $db->prepare('DELETE FROM siswa WHERE nis = :nis');
        $stmt->execute([':nis' => $nis]);

        http_response_code(204);
        
    break;    

}

header('Content-Type: application/json');
echo json_encode($response);