<template>
  <FormBase data-testid="edit-smart-playlist-form">
    <form @submit.prevent="submit" @keydown.esc="maybeClose">
      <header>
        <h1>Edit Smart Playlist</h1>
      </header>

      <main class="space-y-5">
        <FormRow :cols="2">
          <FormRow>
            <template #label>Name</template>
            <TextInput
              v-model="mutablePlaylist.name"
              v-koel-focus name="name"
              placeholder="Playlist name"
              required
            />
          </FormRow>
          <FormRow>
            <template #label>Folder</template>
            <SelectBox v-model="mutablePlaylist.folder_id">
              <option :value="null" />
              <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
            </SelectBox>
          </FormRow>
        </FormRow>

        <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
          <RuleGroup
            v-for="(group, index) in mutablePlaylist.rules"
            :key="group.id"
            :group="group"
            :is-first-group="index === 0"
            @input="onGroupChanged"
          />
          <Btn class="btn-add-group" small success title="Add a new group" uppercase @click.prevent="addGroup">
            <Icon :icon="faPlus" />
            Group
          </Btn>
        </div>
      </main>

      <footer>
        <Btn type="submit">Save</Btn>
        <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
      </footer>
    </form>
  </FormBase>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { reactive, toRef } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { eventBus } from '@/utils/eventBus'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useOverlay } from '@/composables/useOverlay'
import { useSmartPlaylistForm } from '@/composables/useSmartPlaylistForm'

import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const emit = defineEmits<{ (e: 'close'): void }>()
const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const playlist = useModal().getFromContext<Playlist>('playlist')
const folders = toRef(playlistFolderStore.state, 'folders')
const mutablePlaylist = reactive(cloneDeep(playlist))

const isPristine = () => isEqual(mutablePlaylist.rules, playlist.rules)
  && mutablePlaylist.name.trim() === playlist.name
  && mutablePlaylist.folder_id === playlist.folder_id

const {
  Btn,
  FormBase,
  RuleGroup,
  collectedRuleGroups,
  addGroup,
  onGroupChanged,
} = useSmartPlaylistForm(mutablePlaylist.rules)

const close = () => emit('close')

const maybeClose = async () => {
  if (isPristine()) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}

const submit = async () => {
  showOverlay()

  mutablePlaylist.rules = collectedRuleGroups.value

  try {
    await playlistStore.update(playlist, mutablePlaylist)
    toastSuccess(`Playlist "${playlist.name}" updated.`)
    eventBus.emit('PLAYLIST_UPDATED', playlist)
    close()
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}
</script>
