<script lang="tsx">
import { RouterView } from 'vue-router'
import AppCustomRouter from '@/components/AppCustomRouter.vue'

export default defineComponent({
  setup(_, { slots }) {
    return () => (
      <AppCustomRouter>
        <RouterView>{slots}</RouterView>
      </AppCustomRouter>
    )
  },
})
</script>
