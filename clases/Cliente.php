<?php
require_once __DIR__ . '/../conexion/conexion.php';

class Cliente {
    private $pdo;
    private $cedula;
    private $nombre_completo;
    private $telefono_celular;
    private $correo;

    public function __construct(
        string $cedula,
        string $nombre_completo,
        string $telefono_celular,
        string $correo
    ) {
        $this->pdo               = DatabaseConnection::getInstance()->getConnection();
        $this->cedula            = $cedula;
        $this->nombre_completo   = $nombre_completo;
        $this->telefono_celular  = $telefono_celular;
        $this->correo            = $correo;
    }

    /**
     * Busca un cliente por correo; si no existe, lo crea.
     * @return int ID de `clientes.id_cliente`
     */
    public function obtenerORegistrarCliente(): int {
        // Buscar por correo
        $stmt = $this->pdo->prepare(
            "SELECT id_cliente FROM clientes WHERE correo = :correo"
        );
        $stmt->execute([':correo' => $this->correo]);
        if ($row = $stmt->fetch()) {
            return (int)$row['id_cliente'];
        }

        // Insertar nuevo cliente
        $stmt = $this->pdo->prepare(
            "INSERT INTO clientes
                (cedula, nombre_completo, telefono_celular, correo)
             VALUES
                (:cedula, :nombre, :telefono, :correo)"
        );
        $stmt->execute([
            ':cedula'   => $this->cedula,
            ':nombre'   => $this->nombre_completo,
            ':telefono' => $this->telefono_celular,
            ':correo'   => $this->correo,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Registra una sesión en `sesiones`.
     * $datosSesion debe incluir:
     *   tipo_sesion, descripcion_sesion, duracion_sesion,
     *   lugar_sesion, direccion_sesion, estilo_fotografia,
     *   servicios_adicionales, otros_datos,
     *   total_pagar, abono_inicial, fecha_sesion, hora_sesion
     * @return int ID de `sesiones.id_sesion`
     */
    public function reservarSesion(array $datosSesion, int $idCliente): int {
        $sql = "INSERT INTO sesiones (
                    cliente_id, tipo_sesion, descripcion_sesion,
                    duracion_sesion, lugar_sesion, direccion_sesion,
                    estilo_fotografia, servicios_adicionales, otros_datos,
                    total_pagar, abono_inicial, fecha_sesion, hora_sesion
                ) VALUES (
                    :cliente, :tipo, :desc,
                    :duracion, :lugar, :direccion,
                    :estilo, :servicios, :otros,
                    :total, :abono, :fecha, :hora
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cliente'    => $idCliente,
            ':tipo'       => $datosSesion['tipo_sesion'],
            ':desc'       => $datosSesion['descripcion_sesion'] ?? null,
            ':duracion'   => $datosSesion['duracion_sesion'] ?? $datosSesion['duracion'],
            ':lugar'      => $datosSesion['lugar_sesion'],
            ':direccion'  => $datosSesion['direccion_sesion'] ?? null,
            ':estilo'     => $datosSesion['estilo_fotografia'] ?? null,
            ':servicios'  => is_array($datosSesion['servicios_adicionales'])
                                ? implode(',', $datosSesion['servicios_adicionales'])
                                : $datosSesion['servicios_adicionales'] ?? null,
            ':otros'      => $datosSesion['otros_datos'] ?? null,
            ':total'      => $datosSesion['total_pagar'],
            ':abono'      => $datosSesion['abono_inicial'] ?? ($datosSesion['total_pagar'] * 0.3),
            ':fecha'      => $datosSesion['fecha_sesion'] ?? $datosSesion['fecha'],
            ':hora'       => $datosSesion['hora_sesion'] ?? $datosSesion['hora'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Registra un abono inicial en `pagos_sesiones`.
     */
    public function realizarAbonoSesion(
        int $idSesion,
        float $monto,
        string $numeroFactura,
        ?string $metodoPago = null
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO pagos_sesiones
                (sesion_id, monto, numero_factura, metodo_pago)
             VALUES
                (:sesion, :monto, :factura, :metodo)"
        );
        $stmt->execute([
            ':sesion'  => $idSesion,
            ':monto'   => $monto,
            ':factura' => $numeroFactura,
            ':metodo'  => $metodoPago,
        ]);
    }
}
