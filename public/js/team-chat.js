import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js';
import { getAuth, signInAnonymously } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';
import {
    getDatabase,
    limitToLast,
    onValue,
    orderByChild,
    push,
    query,
    ref as databaseRef,
    serverTimestamp,
    set,
} from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js';
import {
    getDownloadURL,
    getStorage,
    ref,
    uploadBytes,
} from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-storage.js';

const configElement = document.getElementById('team-chat-config');
const chat = document.querySelector('[data-team-chat]');

if (configElement && chat) {
    const options = JSON.parse(configElement.textContent || '{}');
    const labels = options.labels || {};
    const translate = (key, fallback, values = {}) => Object.entries(values).reduce(
        (text, [name, value]) => text.replace(`:${name}`, value),
        labels[key] || fallback,
    );
    const messagesElement = chat.querySelector('[data-chat-messages]');
    const statusElement = chat.querySelector('[data-chat-status]');
    const form = chat.querySelector('[data-chat-form]');
    const textInput = chat.querySelector('[data-chat-input]');
    const fileInput = chat.querySelector('[data-chat-file]');
    const fileNameElement = chat.querySelector('[data-chat-file-name]');
    const submitButton = chat.querySelector('[data-chat-submit]');
    const maxFileSize = 20 * 1024 * 1024;

    const setStatus = (message, type = '') => {
        statusElement.textContent = message;
        statusElement.className = `team-chat-status${type ? ` is-${type}` : ''}`;
    };

    const formatTime = (message) => {
        const value = message.createdAtClient || message.createdAt;
        const milliseconds = typeof value === 'number'
            ? value
            : value?.toMillis?.() || Date.now();

        return new Intl.DateTimeFormat(options.locale === 'en' ? 'en-US' : 'vi-VN', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(milliseconds));
    };

    const renderMessages = (messages) => {
        messagesElement.replaceChildren();

        if (!messages.length) {
            const empty = document.createElement('div');
            empty.className = 'team-chat-empty';
            empty.textContent = translate('no_messages', 'No messages yet. Start the conversation.');
            messagesElement.append(empty);
            return;
        }

        messages.forEach((message) => {
            const wrapper = document.createElement('article');
            wrapper.className = `chat-message${message.firebaseUid === options.firebaseUid ? ' mine' : ''}`;

            const author = document.createElement('div');
            author.className = 'chat-message-author';
            author.textContent = message.userName || translate('member', 'Member');
            wrapper.append(author);

            if (message.text) {
                const text = document.createElement('div');
                text.className = 'chat-message-text';
                text.textContent = message.text;
                wrapper.append(text);
            }

            if (message.attachment?.url) {
                const link = document.createElement('a');
                link.className = 'chat-attachment';
                link.href = message.attachment.url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';

                if (message.attachment.type?.startsWith('image/')) {
                    const image = document.createElement('img');
                    image.src = message.attachment.url;
                    image.alt = message.attachment.name || translate('attachment_image', 'Attached image');
                    link.append(image);
                }

                const attachmentLabel = document.createElement('span');
                attachmentLabel.textContent = `📎 ${message.attachment.name || translate('attachment_file', 'Attached file')}`;
                link.append(attachmentLabel);
                wrapper.append(link);
            }

            const meta = document.createElement('div');
            meta.className = 'chat-message-meta';
            meta.textContent = formatTime(message);
            wrapper.append(meta);
            messagesElement.append(wrapper);
        });

        messagesElement.scrollTop = messagesElement.scrollHeight;
    };

    if (!options.enabled) {
        setStatus(translate('firebase_missing', 'Firebase is not configured. Add FIREBASE_* to .env.'), 'error');
        form.querySelectorAll('input, textarea, button').forEach((element) => { element.disabled = true; });
    } else {
        (async () => {
            try {
                const app = initializeApp(options.firebaseConfig);
                const auth = getAuth(app);
                const database = getDatabase(app, options.firebaseConfig.databaseURL);
                const storage = getStorage(app);
                const authResult = await signInAnonymously(auth);
                options.firebaseUid = authResult.user.uid;

                const messagesQuery = query(
                    databaseRef(database, `teamChats/${options.teamId}/messages`),
                    orderByChild('createdAtClient'),
                    limitToLast(100),
                );

                onValue(messagesQuery, (snapshot) => {
                    const messages = Object.entries(snapshot.val() || {})
                        .map(([id, message]) => ({ id, ...message }))
                        .sort((a, b) => (a.createdAtClient || 0) - (b.createdAtClient || 0));
                    renderMessages(messages);
                    setStatus(translate('connected', 'Realtime Database connected • :count messages', { count: messages.length }), 'ok');
                }, (error) => {
                    console.error('team-chat realtime database error', error);
                    setStatus(translate('sync_error', 'Could not sync Realtime Database (:code). Check Rules and the team ID.', { code: error.code || 'unknown' }), 'error');
                });

                fileInput.addEventListener('change', () => {
                    fileNameElement.textContent = fileInput.files[0]?.name || '';
                });

                textInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                        event.preventDefault();
                        if (!submitButton.disabled) form.requestSubmit();
                    }
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const text = textInput.value.trim();
                    const file = fileInput.files[0];

                    if (!text && !file) return;
                    if (file && file.size > maxFileSize) {
                        setStatus(translate('file_too_large', 'Files must be 20 MB or smaller.'), 'error');
                        return;
                    }

                    submitButton.disabled = true;
                    setStatus(file ? translate('uploading', 'Uploading file...') : translate('sending', 'Sending...'));

                    try {
                        let attachment = null;
                        if (file) {
                            const safeName = file.name.replace(/[^a-zA-Z0-9._-]/g, '_');
                            const fileRef = ref(storage, `teamChats/${options.teamId}/${options.firebaseUid}/${Date.now()}_${safeName}`);
                            const upload = await uploadBytes(fileRef, file, { contentType: file.type || 'application/octet-stream' });
                            attachment = {
                                name: file.name,
                                type: file.type || 'application/octet-stream',
                                size: file.size,
                                url: await getDownloadURL(upload.ref),
                            };
                        }

                        const messageRef = push(databaseRef(database, `teamChats/${options.teamId}/messages`));
                        await set(messageRef, {
                            text,
                            attachment,
                            teamId: String(options.teamId),
                            userId: String(options.userId),
                            userName: options.userName,
                            firebaseUid: options.firebaseUid,
                            createdAt: serverTimestamp(),
                            createdAtClient: Date.now(),
                        });

                        form.reset();
                        fileNameElement.textContent = '';
                        textInput.focus();
                        setStatus(translate('sent', 'Sent'), 'ok');
                    } catch (error) {
                        console.error(error);
                        const code = error?.code || 'unknown';
                        if (code.startsWith('storage/')) {
                            fileInput.value = '';
                            fileNameElement.textContent = '';
                            setStatus(translate('storage_error', 'File upload failed (:code). Check Storage Rules.', { code }), 'error');
                        } else if (code === 'PERMISSION_DENIED') {
                            setStatus(translate('database_error', 'Message failed (:code). Check Realtime Database Rules.', { code }), 'error');
                        } else {
                            setStatus(translate('firebase_error', 'Message failed (:code). Check Firebase Authentication and Rules.', { code }), 'error');
                        }
                    } finally {
                        submitButton.disabled = false;
                    }
                });
            } catch (error) {
                console.error(error);
                setStatus(translate('connection_error', 'Could not connect to Firebase. Check the configuration and enable Anonymous Auth.'), 'error');
            }
        })();
    }
}
