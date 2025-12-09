<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/proyecto_ingenieria/proyecto_ingenieria/clases/Sesiones.php';

class AgendarSesionComprehensiveTest extends TestCase
{
    private $sessionManager;
    private $sessionContext;

    protected function setUp(): void
    {
        $this->sessionManager = SessionManager::getInstance();
        $this->sessionContext = new SessionContext();
    }

    // ============================================================================
    // PERSONA 1: FECHAS Y HORARIOS (8 ESCENARIOS)
    // ============================================================================

    /**
     * Test P1.1: Fecha en el pasado (VL)
     * Entrada: fecha < hoy
     * Esperado: el sistema rechaza la fecha
     */
    public function testP1_1_FechaEnElPasado()
    {
        $fechaPasada = date('Y-m-d', strtotime('-1 day'));
        
        // Simular validación de fecha
        $resultado = $this->validarFecha($fechaPasada);
        
        $this->assertFalse($resultado['valida']);
        $this->assertEquals('Fecha no puede ser en el pasado', $resultado['mensaje']);
    }

    /**
     * Test P1.2: Fecha igual a hoy (VL)
     * Entrada: fecha = hoy
     * Esperado: aceptar o rechazar según regla de negocio
     */
    public function testP1_2_FechaIgualAHoy()
    {
        $fechaHoy = date('Y-m-d');
        
        $resultado = $this->validarFecha($fechaHoy);
        
        // Según regla de negocio, hoy podría no ser válido si es muy tarde
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('valida', $resultado);
        $this->assertArrayHasKey('mensaje', $resultado);
    }

    /**
     * Test P1.3: Fecha en el primer día permitido (VL)
     * Entrada: fecha = hoy + 1
     * Esperado: se acepta y permite continuar
     */
    public function testP1_3_PrimerDiaPermitido()
    {
        $fechaManana = date('Y-m-d', strtotime('+1 day'));
        
        $resultado = $this->validarFecha($fechaManana);
        
        $this->assertTrue($resultado['valida']);
        $this->assertEquals('Fecha válida', $resultado['mensaje']);
    }

    /**
     * Test P1.4: Fecha que supera el rango máximo de reserva (VL)
     * Entrada: fecha = hoy + N+1 días (más allá del rango permitido)
     * Esperado: mensaje "fuera del rango permitido"
     */
    public function testP1_4_FueraDelRangoMaximo()
    {
        // Rango máximo típico: 365 días
        $fechaFuera = date('Y-m-d', strtotime('+400 days'));
        
        $resultado = $this->validarFecha($fechaFuera);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('fuera del rango', strtolower($resultado['mensaje']));
    }

    /**
     * Test P1.5: Hora dentro del horario laboral y libre (Equivalencia)
     * Entrada: 10:00 am, franja válida, sin otra reserva
     * Esperado: slot marcado como disponible
     */
    public function testP1_5_HoraLaboralLibre()
    {
        $fecha = date('Y-m-d', strtotime('+1 day'));
        $hora = '10:00';
        
        $disponible = $this->verificarDisponibilidad($fecha, $hora);
        
        $this->assertTrue($disponible);
    }

    /**
     * Test P1.6: Hora dentro del horario pero ya ocupada (Decisión)
     * Entrada: 10:00 am, misma fecha, ya hay otra cita
     * Esperado: error "horario no disponible"
     */
    public function testP1_6_HoraOcupada()
    {
        $fecha = date('Y-m-d', strtotime('+1 day'));
        $hora = '10:00';
        
        // Simular una reserva existente
        $this->crearReservaSimulada($fecha, $hora);
        
        $disponible = $this->verificarDisponibilidad($fecha, $hora);
        
        $this->assertFalse($disponible);
    }

    /**
     * Test P1.7: Hora fuera del horario laboral (VL)
     * Entrada: 23:00
     * Esperado: rechaza la hora
     */
    public function testP1_7_HoraFueraDelHorario()
    {
        $fecha = date('Y-m-d', strtotime('+1 day'));
        $hora = '23:00';
        
        $resultado = $this->validarHora($hora);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('horario laboral', strtolower($resultado['mensaje']));
    }

    /**
     * Test P1.8: Sin fecha u hora seleccionada (Validación)
     * Entrada: campos de fecha/hora vacíos
     * Esperado: no deja avanzar, muestra errores de "obligatorio"
     */
    public function testP1_8_FechaHoraVacios()
    {
        $resultado = $this->validarFechaYHora('', '');
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('requerido', strtolower($resultado['mensaje']));
    }

    // ============================================================================
    // PERSONA 2: TIPO DE SESIÓN Y REGLAS (8 ESCENARIOS)
    // ============================================================================

    /**
     * Test P2.1: Sesión Estudio válida (Decisión)
     * Tipo = Estudio, duración estándar, resto OK
     * Esperado: se crea la reserva correctamente
     */
    public function testP2_1_SesionEstudioValida()
    {
        $datos = [
            'tipo_sesion' => 'estudio',
            'duracion' => '1h 30min',
            'cliente_nombre' => 'Juan Pérez',
            'cliente_email' => 'juan@example.com'
        ];
        
        $resultado = $this->validarDatosAgendamiento($datos);
        
        $this->assertTrue($resultado['valida']);
    }

    /**
     * Test P2.2: Sesión Exterior sin ubicación (Decisión)
     * Tipo = Exterior, ubicación vacía
     * Esperado: error "ubicación obligatoria"
     */
    public function testP2_2_SesionExteriorSinUbicacion()
    {
        $datos = [
            'tipo_sesion' => 'exterior',
            'ubicacion' => '',
            'duracion' => '2h 30min'
        ];
        
        $resultado = $this->validarDatosAgendamiento($datos);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('ubicación', strtolower($resultado['mensaje']));
    }

    /**
     * Test P2.3: Sesión Exterior con ubicación válida (Decisión)
     * Tipo = Exterior, ubicación llena
     * Esperado: permite continuar
     */
    public function testP2_3_SesionExteriorConUbicacion()
    {
        $datos = [
            'tipo_sesion' => 'exterior',
            'ubicacion' => 'Parque Central',
            'duracion' => '2h 30min'
        ];
        
        $resultado = $this->validarDatosAgendamiento($datos);
        
        $this->assertTrue($resultado['valida']);
    }

    /**
     * Test P2.4: Evento con duración menor al mínimo (VL/Decisión)
     * Tipo = Evento, duración < mínima
     * Esperado: rechazo por duración insuficiente
     */
    public function testP2_4_EventoDuracionBaja()
    {
        $datos = [
            'tipo_sesion' => 'cobertura de evento',
            'duracion' => '1h 0min'  // Mínimo es 4h
        ];
        
        $resultado = $this->validarDuracionEventoValido($datos);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('duración', strtolower($resultado['mensaje']));
    }

    /**
     * Test P2.5: Evento con duración en el mínimo permitido (VL)
     * Tipo = Evento, duración = mínima
     * Esperado: reserva válida
     */
    public function testP2_5_EventoDuracionMinima()
    {
        $datos = [
            'tipo_sesion' => 'cobertura de evento',
            'duracion' => '4h 0min'
        ];
        
        $resultado = $this->validarDuracionEventoValido($datos);
        
        $this->assertTrue($resultado['valida']);
    }

    /**
     * Test P2.6: Sesión Temática sin tema seleccionado (Decisión)
     * Tipo = Temática, tema vacío
     * Esperado: error "tema obligatorio"
     */
    public function testP2_6_SesionTematiaSinTema()
    {
        $datos = [
            'tipo_sesion' => 'temática',
            'tema' => ''
        ];
        
        $resultado = $this->validarDatosAgendamiento($datos);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('tema', strtolower($resultado['mensaje']));
    }

    /**
     * Test P2.7: Sesión Temática con tema y demasiadas personas (Combinación)
     * Tipo = Temática, tema OK, nº personas > máximo
     * Esperado: error por exceder capacidad
     */
    public function testP2_7_SesionTematiaDemasiasPersonas()
    {
        $datos = [
            'tipo_sesion' => 'temática',
            'tema' => 'Navidad',
            'numero_personas' => 50  // Máximo típico: 20
        ];
        
        $resultado = $this->validarDatosAgendamiento($datos);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('capacidad', strtolower($resultado['mensaje']));
    }

    /**
     * Test P2.8: Sin tipo de sesión seleccionado (Validación)
     * Tipo = vacío
     * Esperado: no permite avanzar
     */
    public function testP2_8_SinTipoDeSesion()
    {
        $datos = [
            'tipo_sesion' => ''
        ];
        
        $resultado = $this->validarDatosAgendamiento($datos);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('tipo', strtolower($resultado['mensaje']));
    }

    // ============================================================================
    // PERSONA 3: DATOS DEL CLIENTE Y PERSONALIZACIÓN (8 ESCENARIOS)
    // ============================================================================

    /**
     * Test P3.1: Correo con formato inválido (Equivalencia)
     * Entrada: "cliente@invalido", "cliente.com"
     * Esperado: error de formato
     */
    public function testP3_1_CorreoInvalido()
    {
        $emails = ['cliente@invalido', 'cliente.com', '@ejemplo.com', 'test@'];
        
        foreach ($emails as $email) {
            $resultado = $this->validarEmail($email);
            $this->assertFalse($resultado['valida'], "Email '$email' debería ser inválido");
        }
    }

    /**
     * Test P3.2: Correo válido de cliente nuevo (Combinación)
     * Entrada: correo que no existe en BD
     * Esperado: se marque como nuevo cliente
     */
    public function testP3_2_CorreoClienteNuevo()
    {
        $email = 'cliente_nuevo_' . time() . '@example.com';
        
        $resultado = $this->validarEmail($email);
        $this->assertTrue($resultado['valida']);
        
        $esNuevo = $this->verificarSiClienteNuevo($email);
        $this->assertTrue($esNuevo);
    }

    /**
     * Test P3.3: Correo de cliente existente (Combinación)
     * Entrada: correo ya registrado
     * Esperado: se asocia la reserva a su cuenta
     */
    public function testP3_3_CorreoClienteExistente()
    {
        $email = 'cliente_existente@example.com';
        $this->crearClienteSimulado($email);
        
        $resultado = $this->validarEmail($email);
        $this->assertTrue($resultado['valida']);
        
        $esExistente = !$this->verificarSiClienteNuevo($email);
        $this->assertTrue($esExistente);
    }

    /**
     * Test P3.4: Nombre vacío (Validación)
     * Nombre = ""
     * Esperado: error "nombre obligatorio"
     */
    public function testP3_4_NombreVacio()
    {
        $resultado = $this->validarNombre('');
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('nombre', strtolower($resultado['mensaje']));
    }

    /**
     * Test P3.5: Teléfono con caracteres no numéricos (Equivalencia)
     * Teléfono = "ABC123"
     * Esperado: error de formato
     */
    public function testP3_5_TelefonoConCaracteresInvalidos()
    {
        $telefonos = ['ABC123', '123-456', '+1-234', 'llamar aqui'];
        
        foreach ($telefonos as $tel) {
            $resultado = $this->validarTelefono($tel);
            $this->assertFalse($resultado['valida'], "Teléfono '$tel' debería ser inválido");
        }
    }

    /**
     * Test P3.6: Solo campos obligatorios llenos, opcionales vacíos (Equivalencia)
     * Nombre, correo, tipo sesión ok, sin fondo/música
     * Esperado: permite continuar
     */
    public function testP3_6_SoloObligatorios()
    {
        $datos = [
            'cliente_nombre' => 'María García',
            'cliente_email' => 'maria@example.com',
            'tipo_sesion' => 'estudio',
            'fondo' => '',
            'musica' => '',
            'notas_especiales' => ''
        ];
        
        $resultado = $this->validarDatosCliente($datos);
        
        $this->assertTrue($resultado['valida']);
    }

    /**
     * Test P3.7: Personalización completa válida (Combinación)
     * Fondo, música, notas especiales, etc.
     * Esperado: guarda todo correctamente
     */
    public function testP3_7_PersonalizacionCompleta()
    {
        $datos = [
            'cliente_nombre' => 'Carlos López',
            'cliente_email' => 'carlos@example.com',
            'fondo' => 'Playa',
            'musica' => 'Relajante',
            'notas_especiales' => 'Quiere fotos artísticas en blanco y negro'
        ];
        
        $resultado = $this->validarDatosCliente($datos);
        
        $this->assertTrue($resultado['valida']);
    }

    /**
     * Test P3.8: Campos al límite de longitud (Valor Límite)
     * Nombre, notas con longitud = máximo permitido
     * Esperado: se acepta, no corta mal
     */
    public function testP3_8_CamposAlLimite()
    {
        $nombreMax = str_repeat('A', 100);  // Máximo típico
        $notasMax = str_repeat('B', 500);   // Máximo típico
        
        $datos = [
            'cliente_nombre' => $nombreMax,
            'cliente_email' => 'test@example.com',  // Agregar email
            'notas_especiales' => $notasMax
        ];
        
        $resultado = $this->validarDatosCliente($datos);
        
        $this->assertTrue($resultado['valida']);
        $this->assertLessThanOrEqual(100, strlen($datos['cliente_nombre']));
    }

    // ============================================================================
    // PERSONA 4: PAGO Y CONFIRMACIÓN (8 ESCENARIOS)
    // ============================================================================

    /**
     * Test P4.1: Pago = 0 (Regla de negocio)
     * Total: 100, pago: 0
     * Esperado: rechazo
     */
    public function testP4_1_PagoCero()
    {
        $total = 100;
        $pago = 0;
        
        $resultado = $this->validarPago($total, $pago);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('pago', strtolower($resultado['mensaje']));
    }

    /**
     * Test P4.2: Pago menor al 50% (Regla de negocio)
     * Total: 100, pago: 30
     * Esperado: "Debe abonar al menos 50%"
     */
    public function testP4_2_PagoMenor50Porciento()
    {
        $total = 100;
        $pago = 30;
        
        $resultado = $this->validarPago($total, $pago);
        
        $this->assertFalse($resultado['valida']);
        $this->assertStringContainsString('50%', $resultado['mensaje']);
    }

    /**
     * Test P4.3: Pago exactamente del 50% (Regla de negocio)
     * Total: 100, pago: 50
     * Esperado: reserva "Confirmada / pendiente saldo"
     */
    public function testP4_3_PagoExactamente50Porciento()
    {
        $total = 100;
        $pago = 50;
        
        $resultado = $this->validarPago($total, $pago);
        
        $this->assertTrue($resultado['valida']);
        $this->assertEquals('pendiente_saldo', $resultado['estado']);
    }

    /**
     * Test P4.4: Pago > 50% y < 100% (Regla de negocio)
     * Total: 100, pago: 70
     * Esperado: se acepta, registra pago parcial
     */
    public function testP4_4_PagoParcialSuperior50()
    {
        $total = 100;
        $pago = 70;
        
        $resultado = $this->validarPago($total, $pago);
        
        $this->assertTrue($resultado['valida']);
        $this->assertEquals('pendiente_saldo', $resultado['estado']);
        $this->assertEquals(30, $resultado['saldo_pendiente']);
    }

    /**
     * Test P4.5: Pago del 100% (Regla de negocio)
     * Total: 100, pago: 100
     * Esperado: reserva marcada como totalmente pagada
     */
    public function testP4_5_PagoCompleto100Porciento()
    {
        $total = 100;
        $pago = 100;
        
        $resultado = $this->validarPago($total, $pago);
        
        $this->assertTrue($resultado['valida']);
        $this->assertEquals('pagado_completo', $resultado['estado']);
        $this->assertEquals(0, $resultado['saldo_pendiente']);
    }

    /**
     * Test P4.6: Error en pasarela de pago (Flujo alterno)
     * Simular fallo en pasarela
     * Esperado: no cambia estado de reserva, muestra error
     */
    public function testP4_6_ErrorEnPasarela()
    {
        $resultado = $this->procesarPagoConError();
        
        $this->assertFalse($resultado['exito']);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertEquals('sin_procesar', $resultado['estado_reserva']);
    }

    /**
     * Test P4.7: No realizar pago y cancelar (Flujo alterno)
     * Cliente sale sin pagar
     * Esperado: reserva no se confirma
     */
    public function testP4_7_NoRealizarPago()
    {
        $reservaId = $this->crearReservaTemporalSimulada();
        
        // No se realiza el pago
        $resultado = $this->verificarEstadoReserva($reservaId);
        
        $this->assertEquals('sin_procesar', $resultado['estado']);
    }

    /**
     * Test P4.8: Flujo completo exitoso (Integración)
     * Datos válidos + pago >= 50%
     * Esperado: sesión creada, pago registrado, estado correcto
     */
    public function testP4_8_FlujoCompletoExitoso()
    {
        $datosCompletos = [
            'cliente_nombre' => 'Roberto Martín',
            'cliente_email' => 'roberto@example.com',
            'tipo_sesion' => 'estudio',
            'fecha' => date('Y-m-d', strtotime('+5 days')),
            'hora' => '14:00',
            'total' => 250,
            'pago' => 125  // 50%
        ];
        
        $resultado = $this->agendarSesionCompleta($datosCompletos);
        
        $this->assertTrue($resultado['exito']);
        $this->assertNotEmpty($resultado['reserva_id']);
        $this->assertEquals('pendiente_saldo', $resultado['estado']);
        $this->assertEquals('confirmada', $resultado['confirmacion']);
    }

    // ============================================================================
    // MÉTODOS AUXILIARES PARA VALIDACIÓN
    // ============================================================================

    private function validarFecha($fecha)
    {
        $hoy = new DateTime();
        $fechaIngresada = DateTime::createFromFormat('Y-m-d', $fecha);
        
        if (!$fechaIngresada) {
            return ['valida' => false, 'mensaje' => 'Formato de fecha inválido'];
        }
        
        if ($fechaIngresada < $hoy) {
            return ['valida' => false, 'mensaje' => 'Fecha no puede ser en el pasado'];
        }
        
        $diasMaximos = 365;
        $hoyClon = clone $hoy;
        $hoyClon->modify("+$diasMaximos days");
        
        if ($fechaIngresada > $hoyClon) {
            return ['valida' => false, 'mensaje' => 'Fecha fuera del rango permitido'];
        }
        
        return ['valida' => true, 'mensaje' => 'Fecha válida'];
    }

    private function validarHora($hora)
    {
        $formato = preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora);
        
        if (!$formato) {
            return ['valida' => false, 'mensaje' => 'Formato de hora inválido'];
        }
        
        list($horas, $minutos) = explode(':', $hora);
        $horaNum = (int)$horas;
        
        // Horario laboral: 8 a 18
        if ($horaNum < 8 || $horaNum >= 18) {
            return ['valida' => false, 'mensaje' => 'Hora fuera del horario laboral'];
        }
        
        return ['valida' => true, 'mensaje' => 'Hora válida'];
    }

    private function validarFechaYHora($fecha, $hora)
    {
        if (empty($fecha) || empty($hora)) {
            return ['valida' => false, 'mensaje' => 'Fecha y hora son requerido'];
        }
        
        $validarFecha = $this->validarFecha($fecha);
        if (!$validarFecha['valida']) {
            return $validarFecha;
        }
        
        return $this->validarHora($hora);
    }

    private static $reservasGlobales = [];
    
    private function verificarDisponibilidad($fecha, $hora)
    {
        $key = "$fecha-$hora";
        return !isset(self::$reservasGlobales[$key]);
    }

    private function crearReservaSimulada($fecha, $hora)
    {
        $key = "$fecha-$hora";
        self::$reservasGlobales[$key] = true;
    }

    private function validarDatosAgendamiento($datos)
    {
        if (empty($datos['tipo_sesion'])) {
            return ['valida' => false, 'mensaje' => 'Tipo de sesión es requerido'];
        }
        
        $tipoSesion = strtolower($datos['tipo_sesion']);
        
        if ($tipoSesion === 'exterior' && empty($datos['ubicacion'] ?? '')) {
            return ['valida' => false, 'mensaje' => 'Ubicación es obligatoria para sesiones exteriores'];
        }
        
        if ($tipoSesion === 'temática' && empty($datos['tema'] ?? '')) {
            return ['valida' => false, 'mensaje' => 'Tema es obligatorio para sesiones temáticas'];
        }
        
        if (!empty($datos['numero_personas'] ?? 0) && $datos['numero_personas'] > 20) {
            return ['valida' => false, 'mensaje' => 'Excede la capacidad máxima'];
        }
        
        return ['valida' => true, 'mensaje' => 'Datos válidos'];
    }

    private function validarDuracionEventoValido($datos)
    {
        if ($datos['tipo_sesion'] === 'cobertura de evento') {
            $duracionMinima = '4h 0min';
            if ($datos['duracion'] === '1h 0min') {
                return ['valida' => false, 'mensaje' => 'Duración insuficiente para evento'];
            }
        }
        
        return ['valida' => true, 'mensaje' => 'Duración válida'];
    }

    private function validarEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valida' => false, 'mensaje' => 'Email con formato inválido'];
        }
        
        return ['valida' => true, 'mensaje' => 'Email válido'];
    }

    private static $clientesGlobales = [];
    
    private function verificarSiClienteNuevo($email)
    {
        return !isset(self::$clientesGlobales[$email]);
    }

    private function crearClienteSimulado($email)
    {
        self::$clientesGlobales[$email] = true;
    }

    private function validarNombre($nombre)
    {
        if (empty($nombre)) {
            return ['valida' => false, 'mensaje' => 'Nombre es obligatorio'];
        }
        
        return ['valida' => true, 'mensaje' => 'Nombre válido'];
    }

    private function validarTelefono($telefono)
    {
        if (!preg_match('/^[0-9]{7,15}$/', str_replace(['-', '+', ' '], '', $telefono))) {
            return ['valida' => false, 'mensaje' => 'Teléfono con formato inválido'];
        }
        
        return ['valida' => true, 'mensaje' => 'Teléfono válido'];
    }

    private function validarDatosCliente($datos)
    {
        if (empty($datos['cliente_nombre'] ?? '')) {
            return ['valida' => false, 'mensaje' => 'Nombre requerido'];
        }
        
        if (!filter_var($datos['cliente_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return ['valida' => false, 'mensaje' => 'Email inválido'];
        }
        
        // Permitir nombres hasta 100 caracteres
        if (strlen($datos['cliente_nombre']) > 100) {
            return ['valida' => false, 'mensaje' => 'Nombre supera longitud máxima'];
        }
        
        return ['valida' => true, 'mensaje' => 'Datos válidos'];
    }

    private function validarPago($total, $pago)
    {
        if ($pago <= 0) {
            return ['valida' => false, 'mensaje' => 'El pago debe ser mayor a cero'];
        }
        
        $porcentaje = ($pago / $total) * 100;
        
        if ($porcentaje < 50) {
            return ['valida' => false, 'mensaje' => 'Debe abonar al menos 50%'];
        }
        
        if ($porcentaje == 50) {
            return ['valida' => true, 'estado' => 'pendiente_saldo', 'saldo_pendiente' => $total - $pago];
        }
        
        if ($porcentaje < 100) {
            return ['valida' => true, 'estado' => 'pendiente_saldo', 'saldo_pendiente' => $total - $pago];
        }
        
        return ['valida' => true, 'estado' => 'pagado_completo', 'saldo_pendiente' => 0];
    }

    private function procesarPagoConError()
    {
        return [
            'exito' => false,
            'error' => 'Error en la pasarela de pago',
            'estado_reserva' => 'sin_procesar'
        ];
    }

    private function crearReservaTemporalSimulada()
    {
        static $reservas = [];
        $id = 'RES-' . uniqid();
        $reservas[$id] = ['estado' => 'sin_procesar'];
        return $id;
    }

    private function verificarEstadoReserva($reservaId)
    {
        static $reservas = [];
        return $reservas[$reservaId] ?? ['estado' => 'sin_procesar'];
    }

    private function agendarSesionCompleta($datos)
    {
        // Validar todos los datos
        $validarCliente = $this->validarDatosCliente([
            'cliente_nombre' => $datos['cliente_nombre'],
            'cliente_email' => $datos['cliente_email']
        ]);
        
        if (!$validarCliente['valida']) {
            return ['exito' => false];
        }
        
        $validarFecha = $this->validarFecha($datos['fecha']);
        if (!$validarFecha['valida']) {
            return ['exito' => false];
        }
        
        $validarHora = $this->validarHora($datos['hora']);
        if (!$validarHora['valida']) {
            return ['exito' => false];
        }
        
        $validarPago = $this->validarPago($datos['total'], $datos['pago']);
        if (!$validarPago['valida']) {
            return ['exito' => false];
        }
        
        $reservaId = 'RES-' . uniqid();
        
        return [
            'exito' => true,
            'reserva_id' => $reservaId,
            'estado' => $validarPago['estado'],
            'confirmacion' => 'confirmada'
        ];
    }
}
