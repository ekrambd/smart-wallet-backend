const { ethers } = require("ethers");

const wallet = ethers.Wallet.createRandom();

const mnemonicWords = wallet.mnemonic.phrase.split(" ");

const result = {
    address: wallet.address,
    privateKey: wallet.privateKey,
    mnemonic: mnemonicWords.map((word, index) => ({
        serial: index + 1,
        word: word
    }))
};

console.log(JSON.stringify(result, null, 2));