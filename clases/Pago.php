<?php
// Pago.php
require_once __DIR__ . '/../conexion/conexion.php';

abstract class Pago {
    protected float $monto;

    public function __construct(float $monto) {
        $this->monto = $monto;
    }

    /**
     * Factory method: crea la subclase adecuada según el tipo.
     * $datos puede incluir datos extra necesarios (p. ej. titular, email).
     */
    public static function seleccionarMetodoPago(string $tipo, float $monto, array $datos = []): Pago {
        return match (strtolower($tipo)) {
            'tarjeta'   => new TarjetaCredito($monto, $datos['titular'] ?? ''),
            'yappy'     => new Yappy($monto),
            default     => throw new InvalidArgumentException("Método de pago desconocido: $tipo"),
        };
    }

    /**  
     * Devuelve un array con la info de la factura (para pantalla/PDF/email).  
     */
    abstract public function obtenerFactura(): array;

    /**  
     * Nombre legible del método de pago (se usará en la BD).  
     */
    abstract protected function getMetodo(): string;

    /**
     * Registra el pago en la tabla `pagos_sesiones`.
     */
    public function registrarPagoSesion(int $idSesion, string $numeroFactura): void {
        $pdo = DatabaseConnection::getInstance()->getConnection();
        $sql = "INSERT INTO pagos_sesiones
                  (sesion_id, monto, numero_factura, metodo_pago)
                VALUES
                  (:sesion, :monto, :factura, :metodo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sesion'  => $idSesion,
            ':monto'   => $this->monto,
            ':factura' => $numeroFactura,
            ':metodo'  => $this->getMetodo(),
        ]);
    }
}

class TarjetaCredito extends Pago {
    private string $titular;

    public function __construct(float $monto, string $titular) {
        parent::__construct($monto);
        $this->titular = $titular;
    }

    public function obtenerFactura(): array {
        return [
            'metodo'  => 'Tarjeta de Crédito',
            'titular' => $this->titular,
            'monto'   => $this->monto,
            'fecha'   => date('Y-m-d H:i:s'),
        ];
    }

    protected function getMetodo(): string {
        return 'Tarjeta de Crédito';
    }
}

class Yappy extends Pago {
    public function __construct(float $monto) {
        parent::__construct($monto);
    }

    public function obtenerFactura(): array {
        return [
            'metodo' => 'Yappy',
            'monto'  => $this->monto,
            'fecha'  => date('Y-m-d H:i:s'),
        ];
    }

    protected function getMetodo(): string {
        return 'Yappy';
    }

    /**
     * Redirige al usuario al portal de Yappy.
     * Úsalo tras registrar el pago en BD.
     */
    public function redirigir(): void {
        header('Location: https://www.bgeneral.com/yappy/');
        exit;
    }
}
