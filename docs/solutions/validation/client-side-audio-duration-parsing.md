# Client-Side WAV/FLAC Binary Metadata Parsing

## Problem
In Chromium-based browsers, referencing large local audio files (such as WAV or FLAC tracks) via object URLs (`URL.createObjectURL(file)`) inside standard HTML5 `<audio>` elements can sometimes cause `audio.duration` to return `Infinity` or `NaN`. This happens because:
- The browser lacks seeking capabilities for certain container encodings.
- The browser fails to read duration headers dynamically before metadata is resolved.

## Solution
Read the binary header of the WAV or FLAC files directly on the client side using JavaScript's `File.slice()` and `DataView` to calculate precise durations directly from raw file bytes.

### 1. WAV Duration Parsing
WAV files store sample rate, channels, bit depth, and audio size within designated headers:
- `fmt ` chunk:
  - Channels (offset + 10): 2 bytes
  - Sample Rate (offset + 12): 4 bytes
  - Bits Per Sample (offset + 22): 2 bytes
- `data` chunk:
  - Audio Data Size: 4 bytes following chunk ID `'data'`.

**Calculation**:
$$\text{Duration} = \frac{\text{Audio Data Size}}{\text{Sample Rate} \times \text{Channels} \times \left(\frac{\text{Bits Per Sample}}{8}\right)}$$

### 2. FLAC STREAMINFO Parsing
FLAC starts with `'fLaC'` magic bytes, followed by metadata blocks. The first block is STREAMINFO (34 bytes payload starting at file offset 8):
- Byte offsets 18-25 contain a 64-bit block representing:
  - Sample Rate: 20 bits
  - Number of channels: 3 bits (value + 1)
  - Bits per sample: 5 bits (value + 1)
  - Total samples: 36 bits

**Calculation**:
$$\text{Duration} = \frac{\text{Total Samples}}{\text{Sample Rate}}$$

---

## Code Implementation
```javascript
window.parseAudioMetadata = async function (file) {
    const allowedFormats = ['wav', 'flac'];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowedFormats.includes(ext)) {
        throw 'File extension must be WAV or FLAC.';
    }

    const buffer = await file.slice(0, 2048).arrayBuffer();
    const view = new DataView(buffer);

    let bitDepth = 0;
    let duration = 0;

    if (ext === 'wav') {
        const riff = String.fromCharCode(view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3));
        const wave = String.fromCharCode(view.getUint8(8), view.getUint8(9), view.getUint8(10), view.getUint8(11));
        if (riff !== 'RIFF' || wave !== 'WAVE') {
            throw 'Invalid WAV file header.';
        }

        let offset = 12;
        let fmtFound = false;
        let sampleRate = 0;
        let channels = 0;
        let dataSize = 0;

        while (offset < buffer.byteLength - 8) {
            const chunkId = String.fromCharCode(view.getUint8(offset), view.getUint8(offset + 1), view.getUint8(offset + 2), view.getUint8(offset + 3));
            const chunkSize = view.getUint32(offset + 4, true);
            if (chunkId === 'fmt ') {
                fmtFound = true;
                channels = view.getUint16(offset + 10, true);
                sampleRate = view.getUint32(offset + 12, true);
                bitDepth = view.getUint16(offset + 22, true);
            } else if (chunkId === 'data') {
                dataSize = chunkSize;
            }
            offset += 8 + chunkSize;
        }

        if (!fmtFound) {
            throw 'No fmt chunk found in WAV file.';
        }

        if (bitDepth !== 16 && bitDepth !== 24) {
            throw `Unsupported bit depth: ${bitDepth}-bit. Only 16-bit or 24-bit files are allowed.`;
        }

        if (dataSize === 0) {
            dataSize = file.size - offset; 
        }

        if (sampleRate && channels && bitDepth && dataSize) {
            const bytesPerSec = sampleRate * channels * (bitDepth / 8);
            duration = dataSize / bytesPerSec;
        }
    } else if (ext === 'flac') {
        const magic = String.fromCharCode(view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3));
        if (magic !== 'fLaC') {
            throw 'Invalid FLAC file signature.';
        }

        const hi = view.getUint32(18, false);
        const lo = view.getUint32(22, false);

        const sampleRate = hi >>> 12;
        const channels = ((hi >>> 9) & 0x07) + 1;
        bitDepth = ((hi >>> 4) & 0x1F) + 1;
        const totalSamples = (BigInt(hi & 0x0F) << 32n) | BigInt(lo);

        if (bitDepth !== 16 && bitDepth !== 24) {
            throw `Unsupported bit depth: ${bitDepth}-bit. Only 16-bit or 24-bit files are allowed.`;
        }

        if (sampleRate && totalSamples) {
            duration = Number(totalSamples) / sampleRate;
        }
    }

    return { bitDepth, duration };
};
```
