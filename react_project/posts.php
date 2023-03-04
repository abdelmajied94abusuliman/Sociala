<?php require_once("server.php") ?>

<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods:*");



$method = $_SERVER['REQUEST_METHOD'];


switch ($method) {
    case 'GET' :
        $sql = "SELECT * FROM `users`
                INNER JOIN `posts` ON posts.user_id = users.id
                ORDER BY posts.created_at DESC" ;
        $query = $connect->prepare($sql);
        $query->execute();
        $posts = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($posts);
        break;


    case 'POST' :

        $text = $_POST["post"];
        $user_id = $_POST['user_id'];
        if($_FILES["file"] == null){
        $file = "";
        } else {
            $file = $_FILES["file"] ;
        }

        if($file != ""){
            $targetDir = "../src/components/images/";
            $fileName = basename($file["name"]);
            $targetPath = $targetDir . $fileName;
        
            if (move_uploaded_file($file["tmp_name"], $targetPath)) {
            echo "File uploaded successfully";
                $sql = "INSERT INTO posts (user_id , content , post_image)
                        VALUES ( ? , ? , ? )" ;
                $query = $connect->prepare($sql);
                $query->execute([$user_id , $text , $fileName ]);
                break;
            } else {
            echo "Error uploading file";
            }
        } else {
            $sql = "INSERT INTO posts (user_id , content )
                    VALUES ( ? , ? )" ;
            $query = $connect->prepare($sql);
            $query->execute([$user_id , $text ]);
            break;
        }



    case 'DELETE' :
        $sql = "DELETE FROM posts WHERE post_id = ?" ;
        $path = explode('/' , $_SERVER['REQUEST_URI']);
        if(isset($path[4]) && is_numeric($path[4])){
            $query = $connect->prepare($sql);
            $query->execute([$path[4]]);
        }

        $user_id = $path[5];
        
        $sql = "SELECT p.*, u.* , g.group_name
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN groups g ON p.group_id = g.group_id
        WHERE p.user_id = $user_id OR p.group_id IN (
            SELECT group_id
            FROM groups
            WHERE user_id = $user_id
        ) OR p.user_id IN (
            SELECT friend_id
            FROM friends
            WHERE user_id = $user_id AND status = 'accepted'
        ) OR p.group_id IN (
            SELECT group_id
            FROM members
            WHERE user_id = $user_id
        )
        ORDER BY p.created_at DESC"; 

        $stmt = $connect->prepare($sql);
        $stmt->execute();
        $onePostForUserAndAdminAndGroup = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($onePostForUserAndAdminAndGroup);

        break;
}