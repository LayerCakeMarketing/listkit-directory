import { ref, onMounted, onUnmounted } from 'vue'

export function useInfiniteScroll(callback, options = {}) {
  const {
    threshold = 0.1,
    rootMargin = '100px',
    enabled = true
  } = options

  const target = ref(null)
  const isIntersecting = ref(false)
  let observer = null

  const handleIntersect = (entries) => {
    entries.forEach(entry => {
      isIntersecting.value = entry.isIntersecting
      if (entry.isIntersecting && enabled) {
        callback()
      }
    })
  }

  onMounted(() => {
    if (!target.value) return

    observer = new IntersectionObserver(handleIntersect, {
      threshold,
      rootMargin
    })

    observer.observe(target.value)
  })

  onUnmounted(() => {
    if (observer && target.value) {
      observer.unobserve(target.value)
      observer.disconnect()
    }
  })

  return {
    target,
    isIntersecting
  }
}