# SITEMA ERP - TAKAB SYSTEMS

## Descripción General
Se busca craer un sitema ERP (Enterprise Resource Planning) con el fin de centralizar las operaciones de la empresa en un mismo sistema, usando un enfoque modular para cada area de trabajo, 
asigando roles con sus respectivas funcionalidades.

## Índice
1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Convenciones de Endpoints](#convenciones-de-endpoints)
3. [Módulos del Sistema](#módulos-del-sistema)
4
5
6

---

## Arquitectura del Sistema

### Base de Datos Local para Desarrollo
- **erp_takab**: `Copia de la base de datos diseñada para pruebas en fase de desarrollo`

### Base de Datos Externa
- **vwEmpleadosTAKAB**: `Vista no editable de los empleados de TAKAB (No implementada aún)`
- **BDERPTAKAB**: `Base de datos maestra para el sistema alojada en el servidor`

---

## Convenciones de Endpoints

### Nomenclatura de Acciones
- **obtener**: Consultas SELECT (singular o plural)
- **insertar**: Crear nuevos registros
- **actualizar**: Modificar registros existentes
- **eliminar**: Borrado lógico/físico
- **validar**: Validaciones de negocio
- **generar**: Generación de documentos/reportes

---

## Módulos del Sistema

### 1. Inicio de Sesión
### 2. Administrador
### 3. Ventas
### 3. Proyectos
### 4. Almacen
### 5. Inventario

---

## 1. MÓDULO DE INICIO DE SESION

### 1.1 Obtener inicio de sesión
**Endpoint**: ``

**Body Request**:
- `username`: Nombre de usuario de la cuenta (Primer Nombre + Inicial Apelido Paterno + Inicial Apelido Materno)
- `password`: Contraseña de la cuenta.
  
**Response**:
```json
{
  "success": true,
  "data": {
    "num_expediente": 1001,
    "nombre": "Juan Pérez González",
    "genero": "M",
    "unidad_administrativa": "Dirección General",
    "puesto": "Gerente de Proyectos",
    "vigencia_contrato": "Confianza",
    "fecha_alta": "2020-01-15",
    "correo_interno": "juan.perez@takab.com"
  }
}
```

## 2. MÓDULO DE INVENTARIO
## 3. MÓDULO DE COMPRAS
## 4. MÓDULO DE ALMACEN
## 5. MÓDULO DE BANCOS
