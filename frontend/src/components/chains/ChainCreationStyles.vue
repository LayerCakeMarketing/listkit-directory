<template>
  <div></div>
</template>

<style>
/* Chain creation visual enhancements */
.chain-item-enter-active,
.chain-item-leave-active {
  transition: all 0.3s ease;
}

.chain-item-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.chain-item-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Drag and drop visual feedback */
.sortable-ghost {
  opacity: 0.5;
  background: #f3f4f6;
  box-shadow: 0 0 0 2px #6366f1;
}

.sortable-drag {
  opacity: 1 !important;
  transform: rotate(2deg);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  cursor: grabbing !important;
}

/* Connection line styles */
.chain-connection-line {
  position: relative;
}

.chain-connection-line::before {
  content: '';
  position: absolute;
  left: 50%;
  top: -12px;
  width: 2px;
  height: 24px;
  background: linear-gradient(to bottom, transparent, #e5e7eb, transparent);
  transform: translateX(-50%);
}

/* Hover effects for list items */
.chain-list-item {
  transition: all 0.2s ease;
}

.chain-list-item:hover {
  transform: translateY(-1px);
}

/* Number badge pulse animation */
@keyframes pulse-badge {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.chain-number-badge {
  animation: pulse-badge 2s infinite;
}

.chain-number-badge:hover {
  animation: none;
  transform: scale(1.1);
}

/* Modal overlay improvements */
.modal-overlay-enhanced {
  backdrop-filter: blur(4px);
  background: rgba(0, 0, 0, 0.5);
}

/* Enhanced focus styles */
.chain-input-focus:focus-within {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Loading skeleton for lists */
.skeleton-loader {
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>