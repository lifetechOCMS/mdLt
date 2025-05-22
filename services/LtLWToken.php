<?php

class LtLWToken
{
  public static function insertLwtToken($userId, $user_role = "", $expireTime = "600")
    {
        $token = bin2hex(random_bytes(16));

        $tokenModel = new Tb_lwt_tokenization;
        $tokenModel->token = $token;
        $tokenModel->user_id = $userId;
        $tokenModel->lifetech_table_status = '1';
        $tokenModel->user_role = $user_role;
        $tokenModel->expireTime = date("Y-m-d H:i:s", time() + $expireTime);
        $tokenModel->created_at = date("Y-m-d H:i:s");
        $tokenModel->lifetech_general_id = lifetech_general_id();
        $tokenModel->insert();

        LtSession::set('LW_Token', $token);
        return LtResponse::json("Insert successfully", "203", "200");
    }
    
    
   public static function LWT_Token($token, $expireTime=""){
    
    if ($expireTime==""){
        $expireTime="600";
    }
    
    $tokenization_instance = new Tb_lwt_tokenization;
    $sql_query = $tokenization_instance->select()->where('token', '=', $token)->andWhere('lifetech_table_status', '=', '1')->get();
      if(count($sql_query) > 0){
          $tokenization = $sql_query[0];        
          $tokenId = $tokenization->lifetech_general_id;
          $tokenizationDataReturn = ['user_role'=>$tokenization->user_role];
          $expirationTime = strtotime($tokenization->expireTime);
          if(time() > $expirationTime){
             // $tonization_instance->delete('lifetech_general_id', '=', $tokenId);
              $response = LtResponse::json($responseResult="Session Expired", $responseCode="107", $responseCategory="100");
              return $response;
          }else{
               $newExpirationTime = date("Y-m-d H:i:s", time() + $expireTime);
               $tokenization_instance->expireTime = $newExpirationTime; // Current time + expiration time of 10 minutes in seconds
               $tokenization_instance->last_updated = date("Y-m-d H:i:s"); // Current time + expiration time of 10 minutes in seconds
               $tokenization_instance->update('lifetech_general_id', '=', $tokenId);
               $response = LtResponse::json($responseResult= "Token updated", $responseCode="201", $responseCategory="200", $responseData=$tokenizationDataReturn);
               return $response;
          }
       
       
      }else{
          $response = LtResponse::json($responseResult="Incorrect Token", $responseCode="103", $responseCategory="100");
            return $response;
      }
      
   }
   

}

?>