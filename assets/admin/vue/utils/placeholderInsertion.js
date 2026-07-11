const NO_SPACE_BEFORE = /[.,;:!?)}\]'"]/;

/**
 * Build placeholder text with smart spacing around the cursor or selection.
 */
export function formatPlaceholderInsertion(value, start, end, token) {
  if (start !== end) {
    return {
      insertion: token,
      cursorPos: start + token.length,
    };
  }

  const before = value.slice(0, start);
  const after = value.slice(start);

  let prefix = '';
  let suffix = '';

  if (before.length > 0) {
    const lastChar = before[before.length - 1];
    if (lastChar !== ' ' && lastChar !== '\n' && lastChar !== '\t') {
      prefix = ' ';
    }
  }

  if (after.length > 0) {
    const firstChar = after[0];
    if (firstChar !== ' ' && firstChar !== '\n' && firstChar !== '\t' && !NO_SPACE_BEFORE.test(firstChar)) {
      suffix = ' ';
    }
  }

  const insertion = prefix + token + suffix;

  return {
    insertion,
    cursorPos: start + prefix.length + token.length,
  };
}
