<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Extension\Model;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Para modificar el comportamiento de modelos de otro plugins (o del core)
 * podemos crear una extensión de ese modelo.
 *
 * https://facturascripts.com/publicaciones/extensiones-de-modelos
 */
class Variante
{
    // Ejemplo para añadir un método ... añadir el método usado()
    public function generateEAN(): Closure
    {
        return function() {
            $id = $this->idvariante ?? $this->newCode();

            // concatena 200 (código país fake) + 9 dígitos
            $code = '200' . str_pad($id, 9, '0', STR_PAD_LEFT);

            // calculamos los dígitos de control
            $sum = 0;
            $weightFlag = true;
            for ($i = strlen($code) - 1; $i >= 0; $i--) {
                $sum += (int)$code[$i] * ($weightFlag ? 3 : 1);
                $weightFlag = !$weightFlag;
            }
            $code .= (10 - ($sum % 10)) % 10;

            return $code;
        };
    }
    
    // ***************************************
    // ** Métodos disponibles para extender **
    // ***************************************

    public function clear(): Closure
    {
        return function() {
            // tu código aquí
            // se ejecuta cada vez que se instancia un objeto de este modelo. Asigna valores predeterminados.
        };
    }

    public function delete(): Closure
    {
        return function() {
            // tu código aquí
            // delete() se ejecuta una vez realizado el delete() del modelo,
            // cuando ya se ha eliminado el registro de la base de datos
        };
    }

    public function deleteBefore(): Closure
    {
        return function() {
            // tu código aquí
            // deleteBefore() se ejecuta antes de ejecutar el delete() del modelo.
            // Si devolvemos false, impedimos el delete().
        };
    }

    public function save(): Closure
    {
        return function() {
            // tu código aquí
            // save() se ejecuta una vez realizado el save() del modelo,
            // cuando ya se ha guardado el registro en la base de datos

        };
    }

    public function saveBefore(): Closure
    {
        return function() {
            // tu código aquí
            // saveBefore() se ejecuta antes de hacer el save() del modelo.
            // Si devolvemos false, impedimos el save().
        };
    }

    public function saveInsert(): Closure
    {
        return function() {
            // tu código aquí
            // saveInsert() se ejecuta una vez realizado el saveInsert() del modelo,
            // cuando ya se ha guardado el registro en la base de datos
        };
    }

    public function saveInsertBefore(): Closure
    {
        return function() {
            // tu código aquí
            // saveInsertBefore() se ejecuta antes de hacer el saveInsert() del modelo.
            // Si devolvemos false, impedimos el saveInsert().
        };
    }

    public function saveUpdate(): Closure
    {
        return function() {
            // tu código aquí
            // saveUpdate() se ejecuta una vez realizado el saveUpdate() del modelo,
            // cuando ya se ha guardado el registro en la base de datos
        };
    }

    public function saveUpdateBefore(): Closure
    {
        return function() {
            // tu código aquí
            // saveUpdateBefore() se ejecuta antes de hacer el saveUpdate() del modelo.
            // Si devolvemos false, impedimos el saveUpdate().
        };
    }

    public function test(): Closure
    {
        return function() {
            // tu código aquí
            // test se ejecuta justo después del método test del modelo.
            // Si devolvemos false, impedimos el save().
        };
    }

    public function testBefore(): Closure
    {
        return function() {
            // tu código aquí
            // test se ejecuta justo antes del método test del modelo.
            // Si devolvemos false, impedimos el save() y el resto de test().
        };
    }



    /******************
     * Custom functions
     ******************/

    public function generateSku()
    {

    }

}
