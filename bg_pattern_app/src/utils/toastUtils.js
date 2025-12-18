export function showError(toast, message) {
  toast.add({
    severity: 'error',
    summary: 'Ошибка',
    detail: message,
    life: 3000,
  });
  console.log('toast.add() вызван');
}

export function showSuccess(toast, message) {
  toast.add({
    severity: 'success',
    summary: 'Успех',
    detail: message,
    life: 3000,
  });
  console.log('toast.add() вызван');
}

export function showInfo(toast, message) {
  toast.add({
    severity: 'info',
    summary: 'Информация',
    detail: message,
    life: 3000,
  });
  console.log('toast.add() вызван');
}

export function showWarning(toast, message) {
  toast.add({
    severity: 'warn',
    summary: 'Предупреждение',
    detail: message,
    life: 3000,
  });
  console.log('toast.add() вызван');
}
