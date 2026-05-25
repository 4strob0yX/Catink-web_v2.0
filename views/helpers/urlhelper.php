<?php
function basePath(){
    return "";
}
function encodeId($id){
    return rtrim(strtr(base64_encode($id), '+/', '-_'), '=');
}
function decodeId($hash){
    return base64_decode(strtr($hash, '-_', '+/'));
}
// URLs para noticias - soporta múltiples formatos
function newsUrl($id){
    // Formato corto y principal: /n/{hash}
    return basePath() . "/n/" . encodeId($id);
}
function newsUrlLong($id){
    // Formato largo más legible: /noticia/{hash}
    return basePath() . "/noticia/" . encodeId($id);
}
function newsUrlAlt($id){
    // Formato alternativo: /news/{hash}
    return basePath() . "/news/" . encodeId($id);
}
// URLs para categorías - soporta múltiples formatos
function categoryUrl($nombre){
    // Formato: /categoria/{nombre}
    return basePath() . "/categoria/" . urlencode($nombre);
}
function categoryUrlLong($nombre){
    // Formato largo: /noticias/categoria/{nombre}
    return basePath() . "/noticias/categoria/" . urlencode($nombre);
}
// URLs para búsqueda - soporta múltiples formatos
function searchUrl($termino){
    // Formato: /buscar/{termino}
    return basePath() . "/buscar/" . urlencode($termino);
}
function searchUrlLong($termino){
    // Formato largo: /noticias/buscar/{termino}
    return basePath() . "/noticias/buscar/" . urlencode($termino);
}