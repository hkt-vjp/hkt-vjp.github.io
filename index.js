const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const { google } = require('googleapis');

const app = express();
app.use(cors());
app.use(bodyParser.json());

const emailSlots = [];

function createOAuthClient(refreshToken) {
  const oAuth2Client = new google.auth.OAuth2(
    process.env.CLIENT_ID,
    process.env.CLIENT_SECRET,
    process.env.REDIRECT_URI
  );
  oAuth2Client.setCredentials({ refresh_token: refreshToken });
  return oAuth2Client;
}

async function fetchNetflixCode(refreshToken) {
  try {
    const auth = createOAuthClient(refreshToken);
    const gmail = google.gmail({ version: 'v1', auth });
    const res = await gmail.users.messages.list({
      userId: 'me',
      q: 'from:netflix.com subject:(Your login code)',
      maxResults: 1
    });
    const msg = res.data.messages?.[0];
    if (!msg) return null;
    const detail = await gmail.users.messages.get({ userId: 'me', id: msg.id });
    const body = detail.data.payload.parts?.[0]?.body?.data;
    const decoded = Buffer.from(body || '', 'base64').toString('utf-8');
    const match = decoded.match(/\d{6}/);
    return match ? match[0] : null;
  } catch (e) {
    console.error(e.message);
    return null;
  }
}

app.get('/api/emails', async (req, res) => {
  const { email } = req.query;
  if (email) {
    const slot = emailSlots.find((s) => s.email === email);
    if (!slot) return res.status(404).json({ error: 'Email không tồn tại' });
    const code = await fetchNetflixCode(slot.refreshToken);
    return res.json({ ...slot, code });
  }
  const list = await Promise.all(
    emailSlots.map(async (slot) => {
      const code = await fetchNetflixCode(slot.refreshToken);
      return { ...slot, code };
    })
  );
  res.json(list);
});

app.post('/api/emails', (req, res) => {
  const { email, refreshToken } = req.body;
  if (!email || !refreshToken) return res.status(400).json({ error: 'Thiếu thông tin' });
  const exists = emailSlots.find((e) => e.email === email);
  if (exists) return res.status(409).json({ error: 'Email đã tồn tại' });
  emailSlots.push({ email, refreshToken });
  res.json({ email, refreshToken });
});

app.delete('/api/emails/:email', (req, res) => {
  const i = emailSlots.findIndex((e) => e.email === req.params.email);
  if (i === -1) return res.status(404).json({ error: 'Không tìm thấy' });
  emailSlots.splice(i, 1);
  res.json({ success: true });
});

app.listen(3000, () => {
  console.log('Server running on port 3000');
});