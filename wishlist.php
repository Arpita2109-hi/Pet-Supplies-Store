<?php
session_start();
header("Content-Type: application/json");
require_once "db.php";
if(!isset($_SESSION["user_id"])){
    echo json_encode(["success"=>false,"message"=>"Please sign in again."]);
    exit();
}
if(($_SESSION["role"]??"")==="admin"){
    echo json_encode(["success"=>false,"message"=>"Wishlist is available for customers only."]);
    exit();
}
$userId=(int)$_SESSION["user_id"];
$productId=(int)($_POST["product_id"]??0);
if($productId<=0){
    echo json_encode(["success"=>false,"message"=>"Invalid product."]);
    exit();
}
$check=mysqli_prepare($conn,"SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
mysqli_stmt_bind_param($check,"ii",$userId,$productId);
mysqli_stmt_execute($check);
$result=mysqli_stmt_get_result($check);
if(mysqli_num_rows($result)>0){
    $stmt=mysqli_prepare($conn,"DELETE FROM wishlist WHERE user_id=? AND product_id=?");
    mysqli_stmt_bind_param($stmt,"ii",$userId,$productId);
    mysqli_stmt_execute($stmt);
    $inWishlist=false;
}else{
    $stmt=mysqli_prepare($conn,"INSERT INTO wishlist(user_id,product_id) VALUES(?,?)");
    mysqli_stmt_bind_param($stmt,"ii",$userId,$productId);
    mysqli_stmt_execute($stmt);
    $inWishlist=true;
}
$countStmt=mysqli_prepare($conn,"SELECT COUNT(*) AS total FROM wishlist WHERE user_id=?");
mysqli_stmt_bind_param($countStmt,"i",$userId);
mysqli_stmt_execute($countStmt);
$countResult=mysqli_stmt_get_result($countStmt);
$count=(int)mysqli_fetch_assoc($countResult)["total"];
echo json_encode(["success"=>true,"inWishlist"=>$inWishlist,"count"=>$count]);
?>
