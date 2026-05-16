<?php
/**
 * BBR Fragrance - Validation Helpers
 */

function sanitizeString($value) {
    if ($value === null) return null;
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function validateRequired($data, $fields) {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $errors[] = "El campo '{$field}' es requerido.";
        }
    }
    return $errors;
}

function validateNumeric($value, $fieldName, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return "El campo '{$fieldName}' debe ser numerico.";
    }
    if ($min !== null && $value < $min) {
        return "El campo '{$fieldName}' debe ser al menos {$min}.";
    }
    if ($max !== null && $value > $max) {
        return "El campo '{$fieldName}' debe ser maximo {$max}.";
    }
    return null;
}

function validateEmail($email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return "El email no tiene un formato valido.";
    }
    return null;
}

function validateEnum($value, $allowed, $fieldName) {
    if (!in_array($value, $allowed)) {
        return "El campo '{$fieldName}' debe ser uno de: " . implode(', ', $allowed);
    }
    return null;
}

function validateDate($date, $fieldName) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return "El campo '{$fieldName}' debe ser una fecha valida (YYYY-MM-DD).";
    }
    return null;
}

function getPaginationParams() {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? $_GET['limit'] ?? DEFAULT_PAGE_SIZE;
    $limit = min(MAX_PAGE_SIZE, max(1, (int)$perPage));
    $offset = ($page - 1) * $limit;
    return [$page, $limit, $offset];
}
