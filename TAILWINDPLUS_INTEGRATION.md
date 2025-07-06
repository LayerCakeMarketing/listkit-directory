# TailwindPlus Integration Guide for Vue.js

## Converting React/Headless UI Components to Vue 3

### 1. Basic Component Conversion

**React Component:**
```jsx
export default function Card({ title, children, className }) {
  return (
    <div className={`rounded-lg shadow-lg p-6 ${className}`}>
      <h3 className="text-lg font-semibold">{title}</h3>
      <div className="mt-4">{children}</div>
    </div>
  )
}
```

**Vue Equivalent:**
```vue
<script setup>
defineProps({
    title: String,
    className: {
        type: String,
        default: ''
    }
})
</script>

<template>
    <div :class="`rounded-lg shadow-lg p-6 ${className}`">
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <div class="mt-4">
            <slot />
        </div>
    </div>
</template>
```

### 2. Headless UI Replacements

| Headless UI (React) | Vue Alternative |
|-------------------|----------------|
| `@headlessui/react` Dialog | Use your existing `Modal.vue` component |
| `@headlessui/react` Menu | Use your existing `Dropdown.vue` component |
| `@headlessui/react` Switch | Create custom toggle or use native checkbox |
| `@headlessui/react` Listbox | Create custom select or use native select |
| `@headlessui/react` Combobox | Create custom autocomplete component |

### 3. State Management Conversion

**React (useState):**
```jsx
const [isOpen, setIsOpen] = useState(false)
```

**Vue (ref):**
```vue
const isOpen = ref(false)
```

**React (useEffect):**
```jsx
useEffect(() => {
  // side effect
  return () => {
    // cleanup
  }
}, [dependency])
```

**Vue (watch/onMounted):**
```vue
onMounted(() => {
  // side effect
})

onUnmounted(() => {
  // cleanup
})

watch(dependency, (newVal) => {
  // react to changes
})
```

### 4. Event Handling

**React:**
```jsx
<button onClick={() => handleClick(id)}>Click</button>
```

**Vue:**
```vue
<button @click="handleClick(id)">Click</button>
```

### 5. Conditional Rendering

**React:**
```jsx
{isVisible && <Component />}
{condition ? <ComponentA /> : <ComponentB />}
```

**Vue:**
```vue
<Component v-if="isVisible" />
<ComponentA v-if="condition" />
<ComponentB v-else />
```

### 6. List Rendering

**React:**
```jsx
{items.map(item => (
  <Item key={item.id} {...item} />
))}
```

**Vue:**
```vue
<Item v-for="item in items" :key="item.id" v-bind="item" />
```

### 7. Integrating TailwindPlus Components

1. **Copy the HTML structure** and Tailwind classes exactly
2. **Convert React props** to Vue props using `defineProps()`
3. **Replace React children** with Vue slots
4. **Convert state hooks** to Vue refs
5. **Replace event handlers** with Vue event syntax
6. **Handle animations** with Vue's `<Transition>` component

### 8. Example: Converting a TailwindPlus Alert Component

**Original React:**
```jsx
import { XMarkIcon } from '@heroicons/react/24/outline'

export function Alert({ type = 'info', title, message, onClose }) {
  const colors = {
    info: 'bg-blue-50 text-blue-800',
    success: 'bg-green-50 text-green-800',
    warning: 'bg-yellow-50 text-yellow-800',
    error: 'bg-red-50 text-red-800'
  }

  return (
    <div className={`rounded-md p-4 ${colors[type]}`}>
      <div className="flex">
        <div className="flex-1">
          <h3 className="text-sm font-medium">{title}</h3>
          <p className="mt-1 text-sm">{message}</p>
        </div>
        {onClose && (
          <button onClick={onClose} className="ml-3">
            <XMarkIcon className="h-5 w-5" />
          </button>
        )}
      </div>
    </div>
  )
}
```

**Vue Conversion:**
```vue
<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (value) => ['info', 'success', 'warning', 'error'].includes(value)
    },
    title: String,
    message: String,
    closable: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close'])

const colors = {
    info: 'bg-blue-50 text-blue-800',
    success: 'bg-green-50 text-green-800',
    warning: 'bg-yellow-50 text-yellow-800',
    error: 'bg-red-50 text-red-800'
}
</script>

<template>
    <div :class="`rounded-md p-4 ${colors[type]}`">
        <div class="flex">
            <div class="flex-1">
                <h3 class="text-sm font-medium">{{ title }}</h3>
                <p class="mt-1 text-sm">{{ message }}</p>
            </div>
            <button v-if="closable" @click="emit('close')" class="ml-3">
                <XMarkIcon class="h-5 w-5" />
            </button>
        </div>
    </div>
</template>
```

### 9. Using in Your Components

```vue
<template>
    <Alert 
        type="success" 
        title="Success!" 
        message="Your changes have been saved."
        :closable="true"
        @close="showAlert = false"
    />
</template>
```

### 10. Tips for Success

1. **Start with simple components** like buttons, cards, and badges
2. **Use your existing components** as templates for structure
3. **Keep Tailwind classes unchanged** - they work the same in Vue
4. **Test incrementally** - convert one component at a time
5. **Create a components library** in `resources/js/Components/UI/` for TailwindPlus components

### 11. Recommended Workflow

1. Choose a TailwindPlus component/template
2. Create a new Vue file in `resources/js/Components/UI/`
3. Copy the HTML structure and Tailwind classes
4. Convert React logic to Vue Composition API
5. Test in a page component
6. Adjust for your specific needs

The key is that Tailwind CSS classes work identically in Vue - you just need to adapt the JavaScript logic and component structure.